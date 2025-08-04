<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/webhooks')]
class WebhookController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[Route('/mangopay', name: 'webhook_mangopay', methods: ['POST'])]
    public function mangopayWebhook(Request $request): JsonResponse
    {
        try {
            // Récupérer les données du webhook
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                $this->logger->error('MangoPay Webhook: Invalid JSON data');
                return new JsonResponse(['error' => 'Invalid JSON'], 400);
            }

            // Vérifier la présence des champs requis
            if (!isset($data['EventType']) || !isset($data['ResourceId'])) {
                $this->logger->error('MangoPay Webhook: Missing required fields', $data);
                return new JsonResponse(['error' => 'Missing required fields'], 400);
            }

            $eventType = $data['EventType'];
            $resourceId = $data['ResourceId'];
            $date = $data['Date'] ?? time();

            $this->logger->info('MangoPay Webhook received', [
                'event_type' => $eventType,
                'resource_id' => $resourceId,
                'date' => $date,
                'data' => $data
            ]);

            // Traiter selon le type d'événement
            switch ($eventType) {
                case 'PAYIN_NORMAL_SUCCEEDED':
                    $this->handlePayInSucceeded($resourceId, $data);
                    break;
                    
                case 'PAYIN_NORMAL_FAILED':
                    $this->handlePayInFailed($resourceId, $data);
                    break;
                    
                case 'TRANSFER_NORMAL_SUCCEEDED':
                    $this->handleTransferSucceeded($resourceId, $data);
                    break;
                    
                case 'TRANSFER_NORMAL_FAILED':
                    $this->handleTransferFailed($resourceId, $data);
                    break;
                    
                case 'PAYOUT_NORMAL_SUCCEEDED':
                    $this->handlePayOutSucceeded($resourceId, $data);
                    break;
                    
                case 'PAYOUT_NORMAL_FAILED':
                    $this->handlePayOutFailed($resourceId, $data);
                    break;
                    
                case 'KYC_SUCCEEDED':
                    $this->handleKycSucceeded($resourceId, $data);
                    break;
                    
                case 'KYC_FAILED':
                    $this->handleKycFailed($resourceId, $data);
                    break;
                    
                default:
                    $this->logger->warning('MangoPay Webhook: Unhandled event type', [
                        'event_type' => $eventType,
                        'resource_id' => $resourceId
                    ]);
            }

            return new JsonResponse(['status' => 'success']);

        } catch (\Exception $e) {
            $this->logger->error('MangoPay Webhook Error: ' . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $request->getContent()
            ]);
            
            return new JsonResponse(['error' => 'Internal server error'], 500);
        }
    }

    private function handlePayInSucceeded(string $payInId, array $data): void
    {
        $this->logger->info('Processing PayIn succeeded', ['pay_in_id' => $payInId]);
        
        // Trouver le paiement correspondant
        $payment = $this->entityManager->getRepository(Payment::class)
            ->findOneBy(['mangoPayPayInId' => $payInId]);
            
        if (!$payment) {
            $this->logger->warning('Payment not found for PayIn', ['pay_in_id' => $payInId]);
            return;
        }

        // Mettre à jour le statut du paiement
        $payment->setStatus('completed');
        $payment->setProcessedAt(new \DateTimeImmutable());
        
        // Mettre à jour les détails avec les données du webhook
        $paymentDetails = $payment->getPaymentDetails() ?? [];
        $paymentDetails['webhook_data'] = $data;
        $paymentDetails['completed_at'] = date('Y-m-d H:i:s');
        $payment->setPaymentDetails($paymentDetails);

        // Mettre à jour le statut de la commande
        $order = $payment->getOrder();
        if ($order) {
            $order->setStatus('paid');
            $this->logger->info('Order marked as paid', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber()
            ]);
        }

        $this->entityManager->flush();
        
        // TODO: Envoyer une notification email de confirmation
        // TODO: Déclencher l'accès aux galeries/événements
    }

    private function handlePayInFailed(string $payInId, array $data): void
    {
        $this->logger->info('Processing PayIn failed', ['pay_in_id' => $payInId]);
        
        $payment = $this->entityManager->getRepository(Payment::class)
            ->findOneBy(['mangoPayPayInId' => $payInId]);
            
        if (!$payment) {
            $this->logger->warning('Payment not found for PayIn', ['pay_in_id' => $payInId]);
            return;
        }

        // Mettre à jour le statut du paiement
        $payment->setStatus('failed');
        
        // Ajouter les détails de l'erreur
        $paymentDetails = $payment->getPaymentDetails() ?? [];
        $paymentDetails['webhook_data'] = $data;
        $paymentDetails['failed_at'] = date('Y-m-d H:i:s');
        $paymentDetails['failure_reason'] = $data['ResultMessage'] ?? 'Unknown error';
        $payment->setPaymentDetails($paymentDetails);

        // Mettre à jour le statut de la commande
        $order = $payment->getOrder();
        if ($order) {
            $order->setStatus('payment_failed');
            $this->logger->info('Order marked as payment failed', [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber()
            ]);
        }

        $this->entityManager->flush();
        
        // TODO: Envoyer une notification email d'échec
    }

    private function handleTransferSucceeded(string $transferId, array $data): void
    {
        $this->logger->info('Processing Transfer succeeded', ['transfer_id' => $transferId]);
        
        // Logique pour les transferts réussis
        // Typiquement utilisé pour les commissions ou transferts entre wallets
    }

    private function handleTransferFailed(string $transferId, array $data): void
    {
        $this->logger->info('Processing Transfer failed', ['transfer_id' => $transferId]);
        
        // Logique pour les transferts échoués
    }

    private function handlePayOutSucceeded(string $payOutId, array $data): void
    {
        $this->logger->info('Processing PayOut succeeded', ['pay_out_id' => $payOutId]);
        
        // Logique pour les retraits réussis
    }

    private function handlePayOutFailed(string $payOutId, array $data): void
    {
        $this->logger->info('Processing PayOut failed', ['pay_out_id' => $payOutId]);
        
        // Logique pour les retraits échoués
    }

    private function handleKycSucceeded(string $kycDocumentId, array $data): void
    {
        $this->logger->info('Processing KYC succeeded', ['kyc_document_id' => $kycDocumentId]);
        
        // Logique pour la validation KYC réussie
    }

    private function handleKycFailed(string $kycDocumentId, array $data): void
    {
        $this->logger->info('Processing KYC failed', ['kyc_document_id' => $kycDocumentId]);
        
        // Logique pour la validation KYC échouée
    }

    #[Route('/test', name: 'webhook_test', methods: ['GET'])]
    public function testWebhook(): JsonResponse
    {
        // Endpoint de test pour vérifier que les webhooks fonctionnent
        $this->logger->info('Webhook test endpoint called');
        
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => time(),
            'message' => 'Webhook endpoint is working'
        ]);
    }
}