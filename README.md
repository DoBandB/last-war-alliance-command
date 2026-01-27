\#\# ⚖️ Lizenz & Rechtliches \*\*© 2026 \[Dein Name/Pseudonym\]\*\* Dieses Projekt steht unter einer strengen \*\*Nicht-Kommerziellen Lizenz (Non-Commercial License)\*\*. ❌ \*\*Es ist STRENG UNTERSAGT:\*\* \* Diesen Code zu verkaufen. \* Dieses Tool als kostenpflichtigen Hosting-Service für andere Allianzen anzubieten. \* Geld für die Installation oder Nutzung zu verlangen. ✅ \*\*Erlaubt ist:\*\* \* Kostenlose Nutzung für deine eigene Allianz. \* Hosting auf deinem eigenen Server (auf eigene Kosten). \* Anpassungen am Code (Forking), solange das Ergebnis ebenfalls kostenlos und offen bleibt. \> "Keep it free for the community."

\# 🛡️ War Command \- Das ultimative Allianz-Tool für "Last War: Survival"

\!\[Status\](https://img.shields.io/badge/Status-Stable-green) \!\[Docker\](https://img.shields.io/badge/Docker-Ready-blue) \!\[License\](https://img.shields.io/badge/License-MIT-yellow)

\*\*War Command\*\* ist eine leistungsstarke, selbst-gehostete Webanwendung zur Verwaltung und Koordination von Allianzen im Spiel \*Last War: Survival Game\*. Es bietet präzises War-Timing, statistische Auswertungen (Marshall/Duelle) und eine nahtlose Discord-Integration, um deine Allianz zum Sieg zu führen.

Entwickelt für den Betrieb auf einer \*\*Synology NAS\*\* oder jedem \*\*Docker-fähigen Server\*\*.

\---

\#\# 🚀 Key Features

\#\#\# ⚔️ Kriegsführung & Koordination (War Room)  
\* \*\*Präzise Timer:\*\* Setze Timer für Schilde, Buffs, Eroberungen oder Verteidigungsphasen.  
\* \*\*Live-Countdown:\*\* Alle Allianzmitglieder sehen die gleichen Timer in Echtzeit.  
\* \*\*Visuelle Indikatoren:\*\* Farbcodierte Statusanzeigen für verschiedene Event-Typen.  
\* \*\*Discord Push:\*\* Automatische Benachrichtigungen an deinen Discord-Server, wenn ein Timer abläuft oder erstellt wird.

\#\#\# 🚜 Alarm-Systeme  
\* \*\*Bagger/Excavator Alarm:\*\* Ein dedizierter "Panik-Knopf" (\`bagger.html\`) für schnelle Alarmierung bei Feindkontakt am Bagger. Sendet sofortige High-Priority-Alerts an Discord.  
\* \*\*R4/Allianz Nachrichten:\*\* Sende wichtige Ankündigungen direkt aus dem Tool in spezifische Discord-Kanäle.

\#\#\# 📊 Datenanalyse & Stats (Data Intelligence)  
\* \*\*Marshall Guard & Duell Tracking:\*\* Importiere CSV-Daten, um die Leistung deiner Mitglieder zu tracken.  
\* \*\*Highscore-Listen:\*\* Wer sind die Top-Performer der Woche? (Sortierbar nach Punkten).  
\* \*\*Server Power Ranking:\*\* Behalte die Stärke anderer Server im Auge.  
\* \*\*Save Week Management:\*\* Verwalte, welche Wochen für das Ranking zählen (Save/Kill Event Logik).

\#\#\# 🗺️ Karten & Strategie  
\* \*\*Map Builder:\*\* Ein integriertes Tool (\`map\_builder3.html\`) zum Erstellen taktischer Kartenübersichten.  
\* \*\*Koordinaten-System:\*\* Direktes Verlinken von Zielen mit X/Y Koordinaten.

\#\#\# 🔐 Benutzerverwaltung (RBAC)  
\* \*\*Rollenbasiertes System:\*\*  
    \* \*\*R5 (Admin):\*\* Voller Zugriff, Server-Verwaltung, User-Promotions.  
    \* \*\*R4 (Offizier):\*\* Timer setzen, Stats importieren, Nachrichten senden.  
    \* \*\*R1-R3 (Member):\*\* Lesezugriff auf Timer und Stats.  
\* \*\*Sicherheit:\*\* Passwortgeschützte Logins (Hash) und Session-Management.

\---

\#\# 🛠️ Tech Stack

Das Projekt ist bewusst leichtgewichtig gehalten ("Lightweight Monolith"), um auf minimaler Hardware (z.B. Heim-NAS) zu laufen.

\* \*\*Backend:\*\* PHP 8.2 (Native, kein schweres Framework)  
\* \*\*Datenbank:\*\* MariaDB / MySQL 10.6+  
\* \*\*Frontend:\*\* HTML5, Vanilla JS, CSS3 (Dark Mode Theme)  
\* \*\*Container:\*\* Docker & Docker Compose  
\* \*\*Integrationen:\*\* Discord Webhooks, Chart.js (für Visualisierungen)

\---

\#\# 📦 Installation & Setup

Das Tool ist für \*\*Docker Compose\*\* optimiert.

\#\#\# Voraussetzungen  
\* Ein Server/NAS mit Docker & Docker Compose.  
\* (Optional) Eine Domain mit SSL (z.B. via IPv64.net & Let's Encrypt) für den externen Zugriff.

\#\#\# Schnellstart

1\.  \*\*Repository klonen\*\* oder Dateien herunterladen.  
2\.  \*\*Konfiguration anpassen:\*\*  
    \* In \`docker-compose.yml\`: Root- und DB-Passwörter setzen.  
    \* In \`src/db\_setup.php\`: DB-Passwörter angleichen und Discord-Webhooks eintragen.  
    \* In \`src/api.php\`: Einen sicheren \`Master Key\` festlegen.  
3\.  \*\*Starten:\*\*  
    \`\`\`bash  
    docker-compose up \-d \--build  
    \`\`\`  
4\.  \*\*Login:\*\* Rufe die IP deines Servers auf (Port 8080\) und logge dich mit \`admin\` und deinem Master-Key ein.

\---

\#\# 🖼️ Vorschau

<img width="1861" height="837" alt="Screenshot 2026-01-27 172421" src="https://github.com/user-attachments/assets/8008910a-f5b2-43ab-b1e4-fe66e5df8473" />

<img width="1892" height="748" alt="Screenshot 2026-01-27 172358" src="https://github.com/user-attachments/assets/46fef97b-6a8e-4a54-ac4f-a8227fc96bc1" />

\---

\#\# ⚠️ Disclaimer

Dieses Tool ist ein Fan-Projekt und steht in keiner offiziellen Verbindung zu den Entwicklern von "Last War: Survival Game". Nutzung auf eigene Verantwortung.

\---

\#\# 🤝 Contributing

Pull Requests sind willkommen\! Für größere Änderungen bitte erst ein Issue öffnen, um die Idee zu diskutieren.

—

