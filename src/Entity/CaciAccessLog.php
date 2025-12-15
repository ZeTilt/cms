<?php

namespace App\Entity;

use App\Repository\CaciAccessLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaciAccessLogRepository::class)]
#[ORM\Table(name: 'caci_access_logs')]
#[ORM\Index(columns: ['accessed_at'], name: 'idx_caci_access_date')]
#[ORM\Index(columns: ['target_user_id'], name: 'idx_caci_target_user')]
class CaciAccessLog
{
    public const ACTION_VIEW = 'view';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_VALIDATE = 'validate';
    public const ACTION_REJECT = 'reject';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $accessedBy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $targetUser;

    #[ORM\ManyToOne(targetEntity: MedicalCertificate::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?MedicalCertificate $certificate = null;

    #[ORM\Column(length: 20)]
    private string $action;

    #[ORM\Column(length: 50)]
    private string $accessContext;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $accessedAt;

    public function __construct()
    {
        $this->accessedAt = new \DateTimeImmutable();
    }

    public static function create(
        User $accessedBy,
        User $targetUser,
        string $action,
        string $context,
        ?MedicalCertificate $certificate = null,
        ?string $ipAddress = null
    ): self {
        $log = new self();
        $log->accessedBy = $accessedBy;
        $log->targetUser = $targetUser;
        $log->action = $action;
        $log->accessContext = $context;
        $log->certificate = $certificate;
        $log->ipAddress = $ipAddress;

        return $log;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAccessedBy(): User
    {
        return $this->accessedBy;
    }

    public function getTargetUser(): User
    {
        return $this->targetUser;
    }

    public function getCertificate(): ?MedicalCertificate
    {
        return $this->certificate;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getAccessContext(): string
    {
        return $this->accessContext;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getAccessedAt(): \DateTimeImmutable
    {
        return $this->accessedAt;
    }

    public function getActionLabel(): string
    {
        return match($this->action) {
            self::ACTION_VIEW => 'Consultation',
            self::ACTION_DOWNLOAD => 'Téléchargement',
            self::ACTION_VALIDATE => 'Validation',
            self::ACTION_REJECT => 'Rejet',
            default => 'Inconnu'
        };
    }
}
