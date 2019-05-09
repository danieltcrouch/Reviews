#Update averages
SELECT * FROM averageRankings WHERE type = 'Disney' ORDER BY rank, count DESC;

SELECT * FROM averageRankings WHERE type = 'Marvel' ORDER BY rank, count DESC;

SELECT * FROM averageRankings WHERE type = 'StarWars' ORDER BY rank, count DESC;

#Update averages
UPDATE averageRankings AS a
SET
    rank =
        CASE
            WHEN a.type = 'Disney'   THEN (SELECT AVG(d.rank) FROM disneyRankings d     WHERE d.id = a.id)
            WHEN a.type = 'Marvel'   THEN (SELECT AVG(m.rank) FROM marvelRankings m     WHERE m.id = a.id)
            WHEN a.type = 'StarWars' THEN (SELECT AVG(s.rank) FROM starwarsRankings s   WHERE s.id = a.id)
        END,
    count =
        CASE
            WHEN a.type = 'Disney'   THEN (SELECT COUNT(*) FROM disneyRankings d     WHERE d.id = a.id)
            WHEN a.type = 'Marvel'   THEN (SELECT COUNT(*) FROM marvelRankings m     WHERE m.id = a.id)
            WHEN a.type = 'StarWars' THEN (SELECT COUNT(*) FROM starwarsRankings s   WHERE s.id = a.id)
        END;

#Delete non-initial rankings
DELETE FROM `disneyRankings` WHERE initial != 1;
DELETE FROM `marvelRankings` WHERE initial != 1;
DELETE FROM `starwarsRankings` WHERE initial != 1;
