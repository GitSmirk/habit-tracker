<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250823231748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE calendar_events (id SERIAL NOT NULL, habit_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, external_id VARCHAR(50) DEFAULT NULL, external_source VARCHAR(20) DEFAULT NULL, recurrence_rule TEXT DEFAULT NULL, recurrence_end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F9E14F16E7AEB3B2 ON calendar_events (habit_id)');
        $this->addSql('COMMENT ON COLUMN calendar_events.start_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN calendar_events.end_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN calendar_events.recurrence_end_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN calendar_events.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN calendar_events.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE habit (id SERIAL NOT NULL, user_id INT NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, target_frequency INT NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_44FE2172A76ED395 ON habit (user_id)');
        $this->addSql('COMMENT ON COLUMN habit.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN habit.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE habit_completion (id SERIAL NOT NULL, habit_id INT NOT NULL, completion_date DATE NOT NULL, notes TEXT DEFAULT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_AEAF90C5E7AEB3B2 ON habit_completion (habit_id)');
        $this->addSql('COMMENT ON COLUMN habit_completion.completion_date IS \'(DC2Type:date_immutable)\'');
        $this->addSql('COMMENT ON COLUMN habit_completion.completed_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('ALTER TABLE calendar_events ADD CONSTRAINT FK_F9E14F16E7AEB3B2 FOREIGN KEY (habit_id) REFERENCES habit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE habit ADD CONSTRAINT FK_44FE2172A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE habit_completion ADD CONSTRAINT FK_AEAF90C5E7AEB3B2 FOREIGN KEY (habit_id) REFERENCES habit (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE calendar_events DROP CONSTRAINT FK_F9E14F16E7AEB3B2');
        $this->addSql('ALTER TABLE habit DROP CONSTRAINT FK_44FE2172A76ED395');
        $this->addSql('ALTER TABLE habit_completion DROP CONSTRAINT FK_AEAF90C5E7AEB3B2');
        $this->addSql('DROP TABLE calendar_events');
        $this->addSql('DROP TABLE habit');
        $this->addSql('DROP TABLE habit_completion');
        $this->addSql('DROP TABLE "user"');
    }
}
