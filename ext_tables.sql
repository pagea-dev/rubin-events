#
# Table structure for table tx_rubinevents_domain_model_event
#
CREATE TABLE tx_rubinevents_domain_model_event (
    creator INT(11) UNSIGNED DEFAULT '0' NOT NULL,
    contacts TEXT DEFAULT '' NOT NULL,
    map_location VARCHAR(50) DEFAULT '' NOT NULL
);