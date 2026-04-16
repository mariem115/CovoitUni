<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416215853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE rating (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, reviewer_id INT NOT NULL, driver_id INT NOT NULL, reservation_id INT NOT NULL, INDEX IDX_D889262270574616 (reviewer_id), INDEX IDX_D8892622C3423909 (driver_id), UNIQUE INDEX UNIQ_D8892622B83297E7 (reservation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, seats_booked INT DEFAULT 1 NOT NULL, message LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, is_rated TINYINT DEFAULT 0 NOT NULL, passenger_id INT NOT NULL, trip_id INT NOT NULL, INDEX IDX_42C849554502E565 (passenger_id), INDEX IDX_42C84955A5BC2E0E (trip_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE trip (id INT AUTO_INCREMENT NOT NULL, departure VARCHAR(200) NOT NULL, destination VARCHAR(200) NOT NULL, departure_date_time DATETIME NOT NULL, seats_total INT NOT NULL, seats_available INT NOT NULL, price_per_seat NUMERIC(8, 2) DEFAULT NULL, description LONGTEXT DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, driver_id INT NOT NULL, INDEX IDX_7656F53BC3423909 (driver_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, university VARCHAR(150) DEFAULT NULL, bio LONGTEXT DEFAULT NULL, profile_photo VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, is_verified TINYINT DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D889262270574616 FOREIGN KEY (reviewer_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622C3423909 FOREIGN KEY (driver_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849554502E565 FOREIGN KEY (passenger_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A5BC2E0E FOREIGN KEY (trip_id) REFERENCES trip (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trip ADD CONSTRAINT FK_7656F53BC3423909 FOREIGN KEY (driver_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D889262270574616');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622C3423909');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622B83297E7');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849554502E565');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A5BC2E0E');
        $this->addSql('ALTER TABLE trip DROP FOREIGN KEY FK_7656F53BC3423909');
        $this->addSql('DROP TABLE rating');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE trip');
        $this->addSql('DROP TABLE user');
    }
}
