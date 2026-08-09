#
# Table structure for table tx_rubinevents_domain_model_event
#
CREATE TABLE tx_rubinevents_domain_model_event (
    creator INT(11) UNSIGNED DEFAULT '0' NOT NULL,
    contacts TEXT DEFAULT '' NOT NULL,
    map_location VARCHAR(50) DEFAULT '' NOT NULL
);

#
# Marker on the pages created by the page structure importer.
#
# A flag rather than a title match: the shipped structures are translated, so titles differ per
# backend language and would stop identifying anything as soon as a language is added.
#
CREATE TABLE pages (
    tx_rubinevents_example SMALLINT(5) UNSIGNED DEFAULT '0' NOT NULL
);