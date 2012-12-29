

create table if not exists zeiterfassung.mitarbeiter(
  nachname varchar(64) not null,
  vorname varchar(64) not null,
  kuerzel varchar(32) not null,
  manummer int unsigned default 0,
  monatl_arbeitszeit double default 0.0,
  urlaubsanspruch int default 30,
  gutschrift_ukf enum("ja", "nein") default "ja",
  buero enum("ja", "nein") default "nein",
  ze_zustand enum("abwesend", "arbeit", "pause", "buero", "urlaub", "krank", "schule") default "abwesend",
  primary key(kuerzel)
);

