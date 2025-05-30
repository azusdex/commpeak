<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530182433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE call_record (id INT AUTO_INCREMENT NOT NULL, task_id INT NOT NULL, customer_id INT NOT NULL, call_date DATETIME NOT NULL, duration INT NOT NULL, dialed_number VARCHAR(20) NOT NULL, source_ip VARCHAR(20) NOT NULL, ip_continent VARCHAR(255) DEFAULT NULL, phone_continent VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_5EEC4EB38DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE call_stat (id INT AUTO_INCREMENT NOT NULL, task_id INT DEFAULT NULL, customer_id INT NOT NULL, same_calls INT NOT NULL, same_duration INT NOT NULL, total_calls INT NOT NULL, total_duration INT NOT NULL, INDEX IDX_1739987D8DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, parent_task_id INT DEFAULT NULL, type VARCHAR(50) NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', data JSON DEFAULT NULL, result JSON DEFAULT NULL, INDEX IDX_527EDB25FFFE75C0 (parent_task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE call_record ADD CONSTRAINT FK_5EEC4EB38DB60186 FOREIGN KEY (task_id) REFERENCES task (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE call_stat ADD CONSTRAINT FK_1739987D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task ADD CONSTRAINT FK_527EDB25FFFE75C0 FOREIGN KEY (parent_task_id) REFERENCES task (id) ON DELETE SET NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE call_record DROP FOREIGN KEY FK_5EEC4EB38DB60186
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE call_stat DROP FOREIGN KEY FK_1739987D8DB60186
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE task DROP FOREIGN KEY FK_527EDB25FFFE75C0
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE call_record
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE call_stat
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE task
        SQL);
    }
}
