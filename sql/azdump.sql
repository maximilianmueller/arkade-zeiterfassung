

create table if not exists zeiterfassung.azdump(
  dump_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tag date not null,
  kuerzel varchar(32) not null,
  beginn time,
  ende time,
  pause time,
  buero time,
  status enum("ok", "fehler", "korrigiert") default "ok",
  bemerkung varchar(4000) DEFAULT NULL,
  key azdump_tag_kuerzel (tag, kuerzel)
);


