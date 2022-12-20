# bmmi

B-D B I 0 2 X X

## SetUp

### Git

Die Entwicklung der B-Aufgabe findet in einem Git-Repo statt welches
privat auf GitHub, als Backup, gehostet ist.

In dem Repo sind [pre-commit](https://pre-commit.com) hooks installiert, welche
ausgeführt werden müssen, bevor ein Commit der History hinzugefügt werden kann.
Dies erlaubt es automatisch Scripte, welche Test- und Dokumentations-Aufgaben übernehmen, auszuführen.

#### Hooks

* pre-commit-hooks
  * Übernehmen allgemeine Korrekture an Dateien wie Leerzeichen am Ende einer Zeile
* Shellsheck
  * Prüft alle Bash-Scripte auf Fehler
* run-tests
  * Führt das lokale Script run-tests.bash aus
* run-reconcile
  * Führt das lokale Script run-reconcile.bash aus
* Sqlfluff
  * Prüft alle SQL-Statements auf Fehler
* markdownlint
  * Prüft alle Markdown Dateien (wie diese README.md) auf Fehler

### Scripte

* run-compose.bash
  * Übernimmt das starten der [docker-compose](https://docs.docker.com/compose/) Konfiguration
  * Führt das Compose-File in einer [Sops](https://github.com/mozilla/sops) Umgebung aus.
    Dies ermöglicht es Variablen aus einer verschlüsselten Datei (`credentials.yaml`) in die
    Umgebung von Compose zu injezieren. Dies verhindert klartext Passwörter in die
    Versionsverwaltung abzulegen. Die Verschlüsselung erfolgt mittels eines privaten
    GPG-Keys welcher *nicht* diesem Repo hinzugefügt wird, sondern seperat versendet wird.
* run-dump.bash
  * Stellt sicher, dass die lokale MariaDB läuft
  * Erstellt einen Datenbank Dump und legt diesen lokal ab
  * Die Secrets für die Ausführung wurden bereits von Docker-Compose in die Umgebung des
    Containers geladen
* run-reconcile.bash
  * Wrapper für `run-dump.bash`
  * Erstellt einen MD5 Hash des aktuellen Dumps
  * Ersetzt den aktuellen Dump mit einem Neuen und erstellt wieder einen MD5 Hash
  * Gibt einen Fehler aus, falls sich der Hash geändert hat
  * Wird als Pre-Commit Hook verwendet, um beim Commit automatisch einen neuen
    Dump dem Commit hinzuzufügen
  * Dies ermöglicht eine einfache Entwicklung der SPA, da immer ein aktueller Dump dem
    Repo hinzugefügt wird. Wechselt der Entwickler das Arbeitsgerät, kann zu dem aktuellen
    Commit der Dump geladen werden, um gleich Einträge mit Test-Daten zu haben.
* run-restore.bash
  * Verwendet einen lokalen Dump und spielt diesen in die MariaDB der Entwicklungsumgebung ein
* run-tests.bash
  * Wird als Pre-Commit Hook verwendet, um beim Commit Tests der SPA zu automatisieren
  * Tests:
    * php_lint()
      * Sammelt alle `.php` Dateien zusammen und wendet einen
        `php -l` auf diese an, um diese auf Fehler zu überprüfen
    * php_cs()
      * Übernimmt die Aufgabe statischer Codeanalyse
      * Makiert alle Stellen welche gegen den Coding-Standard verstoßen
      * Korrigiert Verstöße, falls möglich
      * Verwendeter Coding-Standard ist [PEAR](https://pear.php.net/manual/en/standards.php)
    * php_stan()
      * Sucht ohne Unit-Tests nach Bugs und makiert diese

### Semantic-Release

Dieses Projekt verwendet [Semantic-Release](https://semantic-release.gitbook.io/semantic-release/) für die Versionierung.

### Docker-Compose

Dieses Projekt verwendet [docker-compose](https://docs.docker.com/compose/) zur Verwaltung der Umgebung.

Es werden zwei Container verwendet:

* webdevops/php-apache:8.0
  * Dieses Image enthält, unter anderem, folgende Tools, welche für die Entwicklung vonnöten sind:
    * [PHP](https://www.php.net) in der Version 8.0.25
    * [Apache Webserver](https://httpd.apache.org) in der Version 2.4.38
    * [Composer](https://getcomposer.org)
  * Es erlaubt einen schnellen Entwicklungsstart, ohne sich mit langer Konfiguration aufhalten zu müssen
* mariadb:10.10.2
  * Dieses Image enthält MariaDB in der Version 10.10.2

#### Updates

Um die enthaltenden Versionen aktuell zu halten, wird [Renovate](https://www.mend.io/free-developer-tools/renovate/) empfohlen.
Renovate kann über GitHub eingebunden werden und verfügt über Plugins für z.B Docker-Compose um aktuell verwendete Versionen zu
erkennen und nach neueren zu Suchen und, falls Änderungsbedarf besteht, einen Pull-Request mit der Aktualisierung zu öffnen.

### DNS

Da es sich um ein lokales Entwicklungsprojekt handelt, welches nicht öffentlich erreichbar sein muss, kann es lokal
über localhost erreicht werden.
Als `http` Port wurde `8080` gewählt, da das Binden dieses Ports nicht über erhöhte Rechte erfolgen muss.
Dieser Port wird von lokal an den internen Port `80` des Containers weitergeleitet, um eine standardisierte
Konfiguration des Apache zu ermöglichen.
Der ebenfalls konfigurierte Port `8443` kann verwendet werden um mittels eines Proxys eine verschlüsselte `https`
Verbindung zu ermöglichen. Der Proxy inklusive Zertifikat ist jedoch nicht im Umfang dieses Repos enthalten.

Als Web-Alias-Domain für den Apache ist `bmmi.unifi.karstensiemer.de` konfiguriert.
Ein DNS-Record welcher für die Verwendung eines [letsencrypt](https://letsencrypt.org) Zertifikats vonnöten ist.
Der A Record zeigt auf den localhost.

## Lösung der B-Aufgabe

Alle Dateien sind in dem Ordner `todo` abgelegt.

Dieser wird über Docker-Compose in den Container mit dem Web-Server gemounted und über diesen
Ausgeliefert.

### Arbeitsflow

Falls ein neuer Entwickler an diesem Projekt arbeiten möchte, muss ein bestehender dessen public GPG-Key von einem
Keyserver abholen, seinem eigenen Keyring hinzufügen und dessen ID in die `.sops.yaml` eintragen.

Nun kann er mittels

```bash
sops updatekeys credentials.yaml
```

die Credentials-Datei neu verschlüsseln, sodass beide
auf den unverschlüsselten Inhalt Zugriff haben.

Falls, wie in diesem Fall, eine Übergabe stattfinden soll und es daher
nur einen einziger GPG-Key geben kann, muss
der GPG-Key ebenfalls übergeben werden.
Dieser muss dann so eingebunden werden, dass sops darauf zugreifen kann.

Falls eine Änderung vorgenommen werden soll oder ein neues Feature hinzugefügt, öffnet der Entwickler einen neuen Branch
in diesem Repo z.B. `feat/add-login`.

Für direktes Anschauen des bisherigen Inhalts startet er das Docker-Compose manifest mit

```bash
./run-compose.bash
```

Die Docker-Container werden gestartet und er kann sich die SPA über seinen Browser anschauen.

Möchte er bereits Test-Daten in die Datenbank laden, um sich Einträge anzuschauen, braucht er nur

```bash
./run-restore.bash
```

ausführen, um einen passenden Datenbankstand zu der bisher geleisteten Arbeit vorzufinden.

Nun fängt er an zu arbeiten bis er gerne einen Test ausführen möchte oder eine Pause machen will.

Dann commitet er seine Arbeit mit z.B.

```bash
git commit -s -am "feat(login): add jquery content in todo.html" && git push
```

Nun werden durch den Commit Hooks ausgelöst welche ihm direktes Feedback zu seiner Arbeit liefern.
Sind Fehler vorhanden, welche von den Tests identifiziert werden konnten, schlägt der Commit fehl
und sein Fehler wird ihm begründet angezeigt.

Nun kann er den Commit-Befehl ein weiteres Mal ausführen. Hat er alle Fehler beseitigt, gelingt der Commit.
Nimmt er nach seiner Pause nun die Arbeit wieder auf und erstellt weitere Änderungen, kann er diese an dem
Commit mit Anhängen über

```bash
git commit --am --no-edit -a -s && git push --force-with-lease
```

So muss er sich nicht für jede Änderung eine neue Nachricht ausdenken und die Commits nicht squashen bevor
er einen Pull-Request erstellt, dies erleichtert auch den Review-Prozess.
Der `--force-with-lease` stellt sicher, dass der Remote-Head seines Branches mit dem aktuellen Hash überschrieben
werden kann, was für einen Amend vonnöten ist. Jedoch Commits die von anderen Teilnehmern des Projekts, welche auch an
dem gleichen Branch arbeiten, verschont bleiben.

Ist sein Pull-Request erstmal geöffnet, werden die Hooks über die workflows in `.github` ausgeführt und der Reviewer
kann direkt in dem Pull-Request sehen, ob alle Tests erfolgreich ausgeführt werden können.

Wird der Pull-Request gemerged, wird automatisch Semantic-Release im `main` Branch ausgeführt, welcher geleistete
Commit-Messages analysiert und anhand derer, eine neue Versionsnummer festlegt und einen Release durchführt.

#### Falls Verschlüsselung nicht erwünscht ist

Falls Verschlüsselung nicht erwünscht ist, können statt der Umgebungsvariablen auch einfach direkt Passwörter
in die `docker-compose.yml` Datei geschrieben werden und Compose mittels

```bash
docker-compose up -d
```

gestartet werden.

Zu ersetzende Variablen:

* `DATABASE`
  * z.B. mit `bmmi`
* `DATABASE_USER`
  * z.B. mit `bmmi`
* `DATABASE_PASSWORD`
  * z.B. `ichBinEinPasswort`

Dem ist noch hinzuzufügen, dass das Verwenden von Umgebungsvariablen zwar
etwas umständlicher ist, jedoch kann so an mehreren Orten die gleiche Variable
verwendet werden, um so das Vergessen einer Änderung zu verhindern, wenn man
einen Wert ändern möchte. Statt in mehreren Zeilen `bmmi` für die Datenbank
auf `beispiel` zu ändern, ändert man nur die Umgebungsvariable und kann
keinen fehler mehr machen.
