--
-- Example content for EXT:rubin_events.
--
-- Imported by the "Import examples" button of the Web > Events module
-- (PageaDev\RubinEvents\Service\ExampleContentImporter).
--
-- Everything in here is invented. Names, addresses, mail addresses (@example.org is reserved for
-- documentation by RFC 2606) and coordinates are placeholders and do not refer to any real person,
-- club or place.
--
-- Placeholders resolved by the importer:
--
--   ###PID###                   uid of the storage folder created for this import
--   ###FEUSER:<username>###     uid of the fe_user with that username, imported further below
--   ###FILE:<filename>###       uid of the sys_file for that file from ./images/
--   ###DATE:<offset>|<HH:MM>### unix timestamp, offset relative to today, e.g. "-28 days|09:00"
--
-- Statements are executed in the order fe_users first, everything else after, so the fe_user
-- placeholders can be resolved. Do not rely on uids here: they are assigned by the database.
--

-- -----------------------------------------------------------------------------------------------
-- Contacts
-- -----------------------------------------------------------------------------------------------

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.reinhardt', '!', 'Anke Reinhardt', 'Anke', 'Reinhardt', 'anke.reinhardt@example.org', '05555 100100', 'Vorsitz', 1);

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.kowalski', '!', 'Bernd Kowalski', 'Bernd', 'Kowalski', 'bernd.kowalski@example.org', '05555 100101', 'Kassenwart', 1);

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.voss', '!', 'Clara Voss', 'Clara', 'Voss', 'clara.voss@example.org', '05555 100102', 'Schriftführung', 1);

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.muehlbach', '!', 'Dieter Mühlbach', 'Dieter', 'Mühlbach', 'dieter.muehlbach@example.org', '05555 100103', 'Gerätewart', 1);

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.sarikaya', '!', 'Elif Sarikaya', 'Elif', 'Sarikaya', 'elif.sarikaya@example.org', '05555 100104', 'Jugendwart', 1);

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.otterbein', '!', 'Frank Otterbein', 'Frank', 'Otterbein', 'frank.otterbein@example.org', '05555 100105', 'Platzwart', 1);

INSERT INTO fe_users (pid, tstamp, crdate, username, password, name, first_name, last_name, email, telephone, title, image)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 'example.lindqvist', '!', 'Greta Lindqvist', 'Greta', 'Lindqvist', 'greta.lindqvist@example.org', '05555 100106', 'Pressewart', 1);

-- -----------------------------------------------------------------------------------------------
-- Contact photos
-- -----------------------------------------------------------------------------------------------

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person1.webp###, ###FEUSER:example.reinhardt###, 'fe_users', 'image', 1);

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person2.webp###, ###FEUSER:example.kowalski###, 'fe_users', 'image', 1);

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person3.webp###, ###FEUSER:example.voss###, 'fe_users', 'image', 1);

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person4.webp###, ###FEUSER:example.muehlbach###, 'fe_users', 'image', 1);

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person5.webp###, ###FEUSER:example.sarikaya###, 'fe_users', 'image', 1);

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person6.webp###, ###FEUSER:example.otterbein###, 'fe_users', 'image', 1);

INSERT INTO sys_file_reference (pid, tstamp, crdate, uid_local, uid_foreign, tablenames, fieldname, sorting_foreign)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###FILE:person7.webp###, ###FEUSER:example.lindqvist###, 'fe_users', 'image', 1);

-- -----------------------------------------------------------------------------------------------
-- Events — three past, one today, four upcoming
-- -----------------------------------------------------------------------------------------------

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:-28 days|09:00###, ###DATE:-28 days|13:00###,
        'Arbeitseinsatz am Bootssteg',
        'Gemeinsames Ausbessern der Stegplanken und des Geländers.',
        'Wir bessern die Stegplanken aus und streichen das Geländer neu.\n\nWerkzeug ist vorhanden, Arbeitshandschuhe bitte selbst mitbringen. Für Verpflegung ist gesorgt.',
        'Bootssteg Nordufer, Beispielsee', '51.0021,10.0043',
        '###FEUSER:example.otterbein###,###FEUSER:example.muehlbach###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:-14 days|19:00###, ###DATE:-14 days|21:00###,
        'Vortrag: Gewässerökologie für Einsteiger',
        'Ein Abend über Nährstoffkreisläufe, Uferbewuchs und was das für uns bedeutet.',
        'Ein Abend über Nährstoffkreisläufe, Uferbewuchs und die Frage, warum manche Abschnitte veralgen und andere nicht.\n\nKeine Vorkenntnisse nötig. Im Anschluss bleibt Zeit für Fragen.',
        'Vereinsraum, Musterweg 5, Musterstadt', '51.0107,10.0129',
        '###FEUSER:example.lindqvist###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:-3 days|19:30###, ###DATE:-3 days|22:00###,
        'Monatstreff im Vereinsraum',
        'Offener Austausch, jeden Monat, ohne Anmeldung.',
        'Offener Austausch bei Getränken. Kurzer Rückblick auf den letzten Monat, danach freies Gespräch.\n\nGäste sind ausdrücklich willkommen.',
        'Vereinsraum, Musterweg 5, Musterstadt', '51.0107,10.0129',
        '###FEUSER:example.reinhardt###,###FEUSER:example.kowalski###,###FEUSER:example.voss###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:+0 days|17:30###, ###DATE:+0 days|20:00###,
        'Schnupperabend für Neumitglieder',
        'Alles Wichtige für den Einstieg, Ausrüstung wird gestellt.',
        'Wer überlegt beizutreten, bekommt hier einen Überblick: Ablauf, Beiträge, Gewässerordnung.\n\nAusrüstung wird gestellt. Anmeldung ist nicht nötig, kommt einfach vorbei.',
        'Vereinsraum, Musterweg 5, Musterstadt', '51.0107,10.0129',
        '###FEUSER:example.sarikaya###,###FEUSER:example.reinhardt###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:+4 days|14:00###, ###DATE:+4 days|22:00###,
        'Sommerfest an der Festwiese',
        'Mit Grillstand, Tombola und Musik ab dem Nachmittag.',
        'Der Nachmittag beginnt gemütlich, ab 18 Uhr wird gegrillt, danach Musik bis in den Abend.\n\nDie Tombola-Lose gibt es am Eingang. Der Erlös geht in die Jugendarbeit.',
        'Festwiese am Beispielsee', '51.0038,10.0061',
        '###FEUSER:example.reinhardt###,###FEUSER:example.otterbein###,###FEUSER:example.lindqvist###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:+11 days|10:00###, ###DATE:+11 days|15:00###,
        'Jugendtag am Nordufer',
        'Für alle ab 10 Jahren, Betreuung und Material inklusive.',
        'Ein Tag für die Jüngeren: Material, Betreuung und Mittagessen sind dabei.\n\nEltern können gern bleiben. Treffpunkt ist der Parkplatz am Nordufer.',
        'Nordufer, Beispielsee', '51.0021,10.0043',
        '###FEUSER:example.sarikaya###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:+25 days|10:00###, ###DATE:+25 days|16:00###,
        'Herbstwanderung um den Beispielsee',
        'Rund zwölf Kilometer, Einkehr unterwegs.',
        'Rund zwölf Kilometer auf dem Uferweg, mit Einkehr auf halber Strecke.\n\nFestes Schuhwerk empfohlen. Bei Dauerregen fällt die Wanderung aus.',
        'Parkplatz Südufer, Beispielsee', '51.0004,10.0088',
        '###FEUSER:example.voss###,###FEUSER:example.muehlbach###');

INSERT INTO tx_rubinevents_domain_model_event (pid, tstamp, crdate, event_start, event_end, title, teaser, description, location, map_location, contacts)
VALUES (###PID###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), ###DATE:+45 days|19:00###, ###DATE:+45 days|22:00###,
        'Jahreshauptversammlung',
        'Berichte, Entlastung und Wahl des Vorstands.',
        'Tagesordnung: Berichte des Vorstands, Kassenbericht, Entlastung, Wahlen, Anträge.\n\nAnträge bitte zwei Wochen vorher schriftlich einreichen. Stimmberechtigt sind alle Mitglieder.',
        'Vereinsraum, Musterweg 5, Musterstadt', '51.0107,10.0129',
        '###FEUSER:example.reinhardt###,###FEUSER:example.kowalski###');
