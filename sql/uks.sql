

create table if not exists zeiterfassung.uks(
  uks_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  kuerzel varchar(32) not null,
  typ enum("urlaub", "krank", "schule", "sonstiges"),
  beginn date not null,
  ende date,
  bemerkung varchar(4000) DEFAULT NULL
);


