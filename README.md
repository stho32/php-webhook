# GitHub Repository Auto-Updater

Ein sicheres System zur Überwachung von GitHub-Repository-Updates mit automatischer Ausführung von Aktionen bei Änderungen.

## Komponenten

### 1. PHP Webhook-Handler
- Empfängt GitHub Webhooks
- Validiert Webhook-Signaturen
- Speichert Push-Informationen in der Datenbank
- Bietet eine sichere API für Abfragen

### 2. PowerShell Auto-Updater
- Überwacht Repository-Änderungen
- Führt konfigurierte Aktionen bei Änderungen aus
- Unterstützt sichere Befehlsausführung
- Umfangreiches Logging

## Installation

### 1. Datenbank einrichten
1. Erstellen Sie eine MySQL-Datenbank
2. Führen Sie das SQL-Script aus:
   ```bash
   mysql -u your_user -p your_database < php-webhook/database.sql
   ```

### 2. PHP Webhook konfigurieren
1. Kopieren Sie `.env.example` zu `.env`:
   ```bash
   cd php-webhook
   cp .env.example .env
   ```
2. Bearbeiten Sie `.env` und setzen Sie:
   - Datenbank-Zugangsdaten
   - GitHub Webhook Secret
   - API Key für den PowerShell-Client

3. Konfigurieren Sie den GitHub Webhook:
   - Payload URL: `https://your-domain.com/webhook.php`
   - Content type: `application/json`
   - Secret: Gleicher Wert wie `GITHUB_WEBHOOK_SECRET` in `.env`
   - Ereignisse: Mindestens `push` auswählen

### 3. PowerShell Auto-Updater konfigurieren
1. Erstellen Sie eine `config.json` im PowerShell-Verzeichnis:
   ```json
   {
     "RepositoryInformationUrl": "https://your-domain.com/index.php",
     "WaitTimeInSeconds": 30,
     "GithubUsername": "your-org-or-username",
     "GithubRepository": "your-repo",
     "ExecuteOnChange": "your-command",
     "ApiKey": "same-as-in-env-file",
     "AllowedCommands": ["git", "npm", "composer"],
     "LogFile": "autoupdate.log"
   }
   ```

## Sicherheitsfeatures

### PHP Webhook
- Webhook Signature Validation
- API Key Authentication
- Rate Limiting
- Input Sanitization
- Prepared Statements
- Fehlerprotokollierung
- Sichere Konfigurationsverwaltung

### PowerShell Auto-Updater
- TLS 1.2 Enforcement
- API Key Authentication
- Allowlist für ausführbare Befehle
- Isolierte Befehlsausführung
- Umfangreiches Logging
- Fehlerbehandlung
- Konfigurationsvalidierung

## API Endpunkte

### webhook.php
- Methode: POST
- Header: 
  - `X-Hub-Signature-256`: GitHub HMAC SHA-256 Signature
- Payload: GitHub Webhook JSON Payload

### index.php
- Methode: GET
- Header:
  - `X-API-Key`: API Key für Authentifizierung
- Parameter:
  - `user`: GitHub Username/Organization
  - `repository`: Repository Name

## Logging
- PHP Logs werden im Standard PHP Error Log gespeichert
- PowerShell Logs werden in `autoupdate.log` gespeichert

## Fehlerbehebung

### Webhook Probleme
1. Prüfen Sie die PHP Error Logs
2. Validieren Sie das Webhook Secret
3. Überprüfen Sie die Datenbank-Verbindung

### Auto-Updater Probleme
1. Prüfen Sie `autoupdate.log`
2. Validieren Sie die API-Verbindung
3. Überprüfen Sie die Berechtigungen für Befehle

## Sicherheitshinweise
1. Verwenden Sie starke, einzigartige Secrets und API Keys
2. Halten Sie die AllowedCommands-Liste minimal
3. Verwenden Sie HTTPS für alle Verbindungen
4. Regelmäßige Überprüfung der Logs
5. Backup der Datenbank
