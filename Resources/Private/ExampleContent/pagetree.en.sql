--
-- Example page structure for EXT:rubin_events — English.
--
-- Imported by the "Import page structure" button of the Web > Events module
-- (PageaDev\RubinEvents\Service\PageTreeImporter). It creates everything the three plugins need to
-- work together: a list page, a slider page, a detail page and the storage folder the events live
-- in. Afterwards the importer points the extension configuration at that storage folder.
--
-- The importer picks the file by the backend language of the user who clicks the button:
-- pagetree.<language code>.sql, falling back to this one. Adding another language means adding
-- another file, nothing else.
--
-- The pages are created with nav_hide = 1 on purpose. This runs against sites that are already
-- live, and an importer has no business putting new entries into someone's main navigation.
--
-- Placeholders resolved by the importer:
--
--   ###PARENT###        uid of the page the structure is attached to (the site root page)
--   ###PAGE:<key>###    uid of a page created earlier in this file, keyed by its "-- @as" line
--   ###COLPOS###        colPos from the extension setting defaultColPos
--
-- Two rules for editing this file:
--
--   1. A statement ends with a semicolon at the end of a line. Never let a line inside a value end
--      with one, or the statement is split in the middle.
--   2. "-- @as <key>" on the line before an INSERT into `pages` registers that page under <key>,
--      so later statements can reference its uid. Statements run in file order, and a key has to
--      exist before it is used.
--
-- Slugs are left out here. The importer fills them in from the parent slug and the title, which is
-- the only place that knows where the structure ended up.
--

-- -----------------------------------------------------------------------------------------------
-- Pages
-- -----------------------------------------------------------------------------------------------

-- @as root
INSERT INTO pages (pid, tstamp, crdate, sorting, doktype, title, nav_hide, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody)
VALUES (###PARENT###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 1, 'Rubin Events – example pages', 1, 1, 0, 31, 31, 0);

-- @as list
INSERT INTO pages (pid, tstamp, crdate, sorting, doktype, title, nav_hide, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody)
VALUES (###PAGE:root###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, 1, 'Events', 1, 1, 0, 31, 31, 0);

-- @as slider
INSERT INTO pages (pid, tstamp, crdate, sorting, doktype, title, nav_hide, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody)
VALUES (###PAGE:root###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512, 1, 'Events as slider', 1, 1, 0, 31, 31, 0);

-- @as detail
INSERT INTO pages (pid, tstamp, crdate, sorting, doktype, title, nav_hide, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody)
VALUES (###PAGE:root###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 768, 1, 'Event detail', 1, 1, 0, 31, 31, 0);

-- @as data
INSERT INTO pages (pid, tstamp, crdate, sorting, doktype, title, nav_hide, perms_userid, perms_groupid, perms_user, perms_group, perms_everybody)
VALUES (###PAGE:root###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1024, 254, 'Event data', 1, 1, 0, 31, 31, 0);

-- -----------------------------------------------------------------------------------------------
-- Plugins
--
-- Both list pages carry an archive below the list, so past events stay reachable from the same
-- page. The archive has no detail page of its own, which is why it only gets pidList.
-- -----------------------------------------------------------------------------------------------

INSERT INTO tt_content (pid, tstamp, crdate, sorting, colPos, CType, header, pi_flexform)
VALUES (###PAGE:list###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, ###COLPOS###, 'rubinevents_eventlist', 'Upcoming events', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="settings.storagePid"><value index="vDEF">###PAGE:data###</value></field><field index="settings.pidList"><value index="vDEF">###PAGE:list###</value></field><field index="settings.listStyle"><value index="vDEF">1</value></field><field index="settings.btnMoreBehavior"><value index="vDEF">1</value></field><field index="settings.pidShow"><value index="vDEF">###PAGE:detail###</value></field><field index="settings.limit"><value index="vDEF">10</value></field></language></sheet></data></T3FlexForms>');

INSERT INTO tt_content (pid, tstamp, crdate, sorting, colPos, CType, header, pi_flexform)
VALUES (###PAGE:list###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512, ###COLPOS###, 'rubinevents_eventarchive', 'Archive', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="settings.listStyle"><value index="vDEF">1</value></field><field index="settings.storagePid"><value index="vDEF">###PAGE:data###</value></field><field index="settings.pidList"><value index="vDEF">###PAGE:list###</value></field><field index="settings.limit"><value index="vDEF">10</value></field></language></sheet></data></T3FlexForms>');

INSERT INTO tt_content (pid, tstamp, crdate, sorting, colPos, CType, header, pi_flexform)
VALUES (###PAGE:slider###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, ###COLPOS###, 'rubinevents_eventlist', 'Upcoming events', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="settings.storagePid"><value index="vDEF">###PAGE:data###</value></field><field index="settings.pidList"><value index="vDEF">###PAGE:slider###</value></field><field index="settings.listStyle"><value index="vDEF">0</value></field><field index="settings.btnMoreBehavior"><value index="vDEF">0</value></field><field index="settings.limit"><value index="vDEF">10</value></field></language></sheet></data></T3FlexForms>');

INSERT INTO tt_content (pid, tstamp, crdate, sorting, colPos, CType, header, pi_flexform)
VALUES (###PAGE:slider###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 512, ###COLPOS###, 'rubinevents_eventarchive', 'Archive', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="settings.listStyle"><value index="vDEF">1</value></field><field index="settings.storagePid"><value index="vDEF">###PAGE:data###</value></field><field index="settings.pidList"><value index="vDEF">###PAGE:slider###</value></field><field index="settings.limit"><value index="vDEF">10</value></field></language></sheet></data></T3FlexForms>');

INSERT INTO tt_content (pid, tstamp, crdate, sorting, colPos, CType, header, pi_flexform)
VALUES (###PAGE:detail###, UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 256, ###COLPOS###, 'rubinevents_eventshow', 'Event', '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data><sheet index="sDEF"><language index="lDEF"><field index="settings.storagePid"><value index="vDEF">###PAGE:data###</value></field><field index="settings.pidList"><value index="vDEF">###PAGE:list###</value></field></language></sheet></data></T3FlexForms>');
