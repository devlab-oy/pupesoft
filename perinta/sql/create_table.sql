CREATE TABLE perinta (
            lasku_tunnus INT(11) PRIMARY KEY,
            toimeksianto_tunnus INT(11),
            FOREIGN KEY(lasku_tunnus)
                REFERENCES lasku(tunnus),
            summa DECIMAL(12, 2) DEFAULT 0.00,
            maksettu DECIMAL(12, 2) DEFAULT 0.00,
            luonti TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            tekija INT(11),
            FOREIGN KEY(tekija)
                REFERENCES kuka(tunnus),
            siirto TIMESTAMP,
            paivitys TIMESTAMP,
            tila ENUM('eiperinnassa', 'perinnassa', 'luotu', 'valmis', 'peruttu') DEFAULT 'eiperinnassa',
            vaatii_paivityksen TINYINT(1)
        );

CREATE TABLE perinta_muutoshistoria (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    lasku_tunnus INT(11),
    FOREIGN KEY(lasku_tunnus)
        REFERENCES lasku(tunnus),
    tyyppi ENUM("luonti", "muutos", "peruutus") DEFAULT "luonti",
    aika TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    summa DECIMAL(12,2) DEFAULT 0.00
);

CREATE TABLE perinta_suoritus (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    lasku_viite INT(11),
    FOREIGN KEY(lasku_viite)
        REFERENCES lasku(viite),
    suoritus_tunnus INT(11),
    FOREIGN KEY(suoritus_tunnus)
        REFERENCES suoritus(tunnus),
    luonti TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    kasitelty TINYINT(1) DEFAULT 0
);

CREATE TABLE perinta_tilitys (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    lasku_viite INT(11),
    FOREIGN KEY(lasku_viite)
        REFERENCES lasku(viite),
    summa DECIMAL(12, 2) DEFAULT 0.00,
    maksaja VARCHAR(12) DEFAULT NULL,
    kirjauspvm DATE DEFAULT '0000-00-00',
    maksupvm DATE DEFAULT '0000-00-00',
    luonti TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DELIMITER $$
CREATE FUNCTION Perinta_ToimeksiantoTunnus (ptunnus VARCHAR(15))
RETURNS INT(11)
BEGIN
    DECLARE toimeksianto_tunnus INT(11);
    DECLARE max_val INT(11);

    SET toimeksianto_tunnus = (
                SELECT perinta.toimeksianto_tunnus
                FROM perinta
                JOIN lasku on lasku.tunnus=perinta.lasku_tunnus
                JOIN asiakas on asiakas.tunnus=lasku.liitostunnus
                WHERE asiakas.tunnus = ptunnus AND (perinta.tila = 'luotu')
                LIMIT 1);
    IF toimeksianto_tunnus IS NULL THEN
        SET max_val = (SELECT MAX(perinta.toimeksianto_tunnus) FROM perinta);
        IF max_val IS NULL THEN
            SET toimeksianto_tunnus = 1;
        ELSE
            SET toimeksianto_tunnus = max_val + 1;
        END IF;
    END IF;
    RETURN toimeksianto_tunnus;
END$$
DELIMITER ;
