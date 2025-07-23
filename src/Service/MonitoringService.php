<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class MonitoringService
{
    private const METRICS_TTL = 300; // 5 minutes

    public function __construct(
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    /**
     * Log performance metrics
     */
    public function logPerformance(string $operation, float $executionTime, array $context = []): void
    {
        $this->logger->info('Performance metric', [
            'operation' => $operation,
            'execution_time' => round($executionTime * 1000, 2) . 'ms',
            'memory_usage' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'context' => $context
        ]);

        // Store metrics in cache for dashboard
        $this->storeMetric('performance', [
            'operation' => $operation,
            'time' => $executionTime,
            'timestamp' => time()
        ]);
    }

    /**
     * Log cache operations
     */
    public function logCacheOperation(string $operation, string $key, bool $hit = null): void
    {
        $data = [
            'cache_operation' => $operation,
            'cache_key' => $key,
            'timestamp' => time()
        ];

        if ($hit !== null) {
            $data['cache_hit'] = $hit;
        }

        $this->logger->debug('Cache operation', $data);
        $this->storeMetric('cache', $data);
    }

    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        $this->logger->warning('Security event', [
            'event' => $event,
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'context' => $context
        ]);

        $this->storeMetric('security', [
            'event' => $event,
            'timestamp' => time()
        ]);
    }

    /**
     * Log content operations
     */
    public function logContentOperation(string $operation, string $type, array $context = []): void
    {
        $this->logger->info('Content operation', [
            'operation' => $operation, // create, update, delete, publish
            'content_type' => $type,   // page, article, gallery
            'timestamp' => time(),
            'context' => $context
        ]);

        $this->storeMetric('content', [
            'operation' => $operation,
            'type' => $type,
            'timestamp' => time()
        ]);
    }

    /**
     * Get performance metrics for dashboard
     */
    public function getPerformanceMetrics(): array
    {
        return $this->cache->get('monitoring_performance_summary', function() {
            $metrics = $this->getMetrics('performance');
            
            if (empty($metrics)) {
                return ['average_time' => 0, 'total_operations' => 0, 'operations' => []];
            }

            $totalTime = array_sum(array_column($metrics, 'time'));
            $operationCounts = array_count_values(array_column($metrics, 'operation'));

            return [
                'average_time' => round($totalTime / count($metrics) * 1000, 2), // ms
                'total_operations' => count($metrics),
                'operations' => $operationCounts,
                'last_updated' => time()
            ];
        });
    }

    /**
     * Get cache metrics
     */
    public function getCacheMetrics(): array
    {
        return $this->cache->get('monitoring_cache_summary', function() {
            $metrics = $this->getMetrics('cache');
            
            if (empty($metrics)) {
                return ['hit_rate' => 0, 'total_operations' => 0];
            }

            $hits = count(array_filter($metrics, fn($m) => $m['cache_hit'] ?? false));
            $total = count(array_filter($metrics, fn($m) => isset($m['cache_hit'])));

            return [
                'hit_rate' => $total > 0 ? round(($hits / $total) * 100, 1) : 0,
                'total_operations' => count($metrics),
                'hits' => $hits,
                'misses' => $total - $hits,
                'last_updated' => time()
            ];
        });
    }

    /**
     * Get content activity metrics
     */
    public function getContentMetrics(): array
    {
        return $this->cache->get('monitoring_content_summary', function() {
            $metrics = $this->getMetrics('content');
            
            if (empty($metrics)) {
                return ['total_operations' => 0, 'by_type' => [], 'by_operation' => []];
            }

            return [
                'total_operations' => count($metrics),
                'by_type' => array_count_values(array_column($metrics, 'type')),
                'by_operation' => array_count_values(array_column($metrics, 'operation')),
                'last_updated' => time()
            ];
        });
    }

    /**
     * Get recent security events
     */
    public function getSecurityEvents(int $limit = 50): array
    {
        $metrics = $this->getMetrics('security');
        
        // Sort by timestamp desc and limit
        usort($metrics, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
        
        return array_slice($metrics, 0, $limit);
    }

    /**
     * Clear old metrics (called via cron or command)
     */
    public function cleanupOldMetrics(int $olderThanHours = 24): void
    {
        $cutoff = time() - ($olderThanHours * 3600);
        $types = ['performance', 'cache', 'content', 'security'];
        
        foreach ($types as $type) {
            $metrics = $this->getMetrics($type);
            $filtered = array_filter($metrics, fn($m) => $m['timestamp'] > $cutoff);
            
            $this->cache->delete("monitoring_metrics_{$type}");
            if (!empty($filtered)) {
                $this->cache->set("monitoring_metrics_{$type}", $filtered, self::METRICS_TTL * 12);
            }
        }
        
        $this->logger->info('Monitoring metrics cleanup completed', [
            'cutoff_hours' => $olderThanHours,
            'cutoff_timestamp' => $cutoff
        ]);
    }

    /**
     * Store metric in cache
     */
    private function storeMetric(string $type, array $data): void
    {
        $key = "monitoring_metrics_{$type}";
        $metrics = $this->cache->get($key, fn() => []);
        
        $metrics[] = $data;
        
        // Keep only last 1000 entries per type
        if (count($metrics) > 1000) {
            $metrics = array_slice($metrics, -1000);
        }
        
        $this->cache->set($key, $metrics, self::METRICS_TTL * 12); // 1 hour
        
        // Invalidate summary caches
        $this->cache->delete("monitoring_{$type}_summary");
    }

    /**
     * Get metrics from cache
     */
    private function getMetrics(string $type): array
    {
        return $this->cache->get("monitoring_metrics_{$type}", fn() => []);
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}