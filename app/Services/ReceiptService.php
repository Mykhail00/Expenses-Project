<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Receipt;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManager;
use League\Flysystem\Filesystem;

class ReceiptService
{
    public function __construct(
        private readonly EntityManager $entityManager,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function create(
        Transaction $transaction,
        string $filename,
        string $storageFilename,
        string $mediaType
    ): Receipt {
        $receipt = new Receipt();

        $receipt->setTransaction($transaction);
        $receipt->setFilename($filename);
        $receipt->setStorageFilename($storageFilename);
        $receipt->setMediaType($mediaType);
        $receipt->setCreatedAt(new \DateTime());

        $this->entityManager->persist($receipt);
        $this->entityManager->flush();

        return $receipt;
    }

    public function getById(int $id)
    {
        return $this->entityManager->find(Receipt::class, $id);
    }

    public function delete(Receipt $receipt): void
    {
        $this->filesystem->delete('receipts/'. $receipt->getStorageFilename());

        $this->entityManager->remove($receipt);
        $this->entityManager->flush();
    }
}