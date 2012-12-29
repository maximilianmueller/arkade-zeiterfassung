

create table if not exists zeiterfassung.azlog(
  log_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  tag date not null,
  kuerzel varchar(32) not null,
  typ enum("arbeit", "pause", "buero"),
  beginn time default "00:00:00",
  ende time default "00:00:00",
  dump_flag smallint default 0,
  key azlog_tag_kuerzel (tag, kuerzel)
);


