#Update_Disney_Average_After_Insert
#disneyRankings
#AFTER
#INSERT

INSERT INTO averageRankings
    (type, id, rank, count)
VALUES
    ('Disney',
     NEW.id,
     (SELECT AVG(rank) FROM disneyRankings WHERE id = NEW.id),
     (SELECT COUNT(*) FROM disneyRankings WHERE id = NEW.id))
ON DUPLICATE KEY UPDATE
    rank = (SELECT AVG(rank) FROM disneyRankings WHERE id = NEW.id),
    count = (SELECT COUNT(*) FROM disneyRankings WHERE id = NEW.id);

#***************************************

#Update_Marvel_Average_After_Insert
#marvelRankings
#AFTER
#INSERT

INSERT INTO averageRankings
    (type, id, rank, count)
VALUES
    ('Marvel',
     NEW.id,
     (SELECT AVG(rank) FROM marvelRankings WHERE id = NEW.id),
     (SELECT COUNT(*) FROM marvelRankings WHERE id = NEW.id))
ON DUPLICATE KEY UPDATE
    rank = (SELECT AVG(rank) FROM marvelRankings WHERE id = NEW.id),
    count = (SELECT COUNT(*) FROM marvelRankings WHERE id = NEW.id);

#***************************************

#Update_StarWars_Average_After_Insert
#starwarsRankings
#AFTER
#INSERT

INSERT INTO averageRankings
    (type, id, rank, count)
VALUES
    ('StarWars',
     NEW.id,
     (SELECT AVG(rank) FROM starwarsRankings WHERE id = NEW.id),
     (SELECT COUNT(*) FROM starwarsRankings WHERE id = NEW.id))
ON DUPLICATE KEY UPDATE
    rank = (SELECT AVG(rank) FROM starwarsRankings WHERE id = NEW.id),
    count = (SELECT COUNT(*) FROM starwarsRankings WHERE id = NEW.id);

#***************************************