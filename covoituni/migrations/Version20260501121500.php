<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260501121500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vehicle information columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD vehicule_marque VARCHAR(100) DEFAULT NULL, ADD vehicule_modele VARCHAR(100) DEFAULT NULL, ADD vehicule_couleur VARCHAR(50) DEFAULT NULL, ADD vehicule_annee INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP vehicule_marque, DROP vehicule_modele, DROP vehicule_couleur, DROP vehicule_annee');
    }
}
