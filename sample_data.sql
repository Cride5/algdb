INSERT INTO algdb_algs(moves, stm, htm, qtm, gen) VALUES
("R U R' U R U2 R'"              ,  7,  7,  8, 2), # SUNE (3x3 or 2x2), U/Sune (COLL)
("y' R U2 R' U' R U' R'"         ,  7,  7,  8, 2), # A-SUNE (3x3 or 2x2) U/A-Sune (COLL)
("R' F R' B2 R F' R' B2 R2"      ,  9,  9, 12, 3), # A-Perm (a) 3x3x3, corner swap 2x2x2
("(x') R' D R' U2 R D' R' U2 R2" ,  9,  9, 12, 3), # A-Perm (a)
("R2' U' R2 U2 F2 U' R2"         ,  7,  7, 12, 3), # UF Adjacent Swap (PBL)
("R2' U R2 U2 y' R2' U R2"       ,  7,  7, 12, 2), # Swap UB/DB Corners (PBL)
("R2 F2 R2"                      ,  3,  3,  6, 2), # U+D diagonal swap (PBL)
("F U R U' R' F'"                ,  6,  6,  6, 3); # Adjacent edge flip (3x3x3), Headlights (2x2x2)

INSERT INTO algdb_groups(group_name, puzzle_id, is_closed) VALUES
("OLL", 2, 0),
("PBL", 2, 0),
("CLL", 2, 0),
("OLL", 3, 0),
("PLL", 3, 0),
("CLL", 3, 0),
("ELL", 3, 0),
("CMLL", 3, 0),
("COLL", 3, 0),
("ZBLL", 3, 0),
("CLS", 3, 0),
("ELS", 3, 0);


INSERT INTO algdb_cases(case_id, group_id, ref_alg, state, case_name) VALUES
(1, 1, 1, 0, "Sune"),
(2, 1, 2, 0, "Anti Sune"),
(3, 1, 8, 0, "Adjacent Edge Flip"),
(1, 2, 1, 0, "Sune"),
(2, 2, 2, 0, "Anti Sune"),
(3, 2, 8, 0, "Headlights"),
(1, 3, 1, 0, "U/Sune"),
(2, 3, 2, 0, "U/A-Sune"),
(1, 4, 4, 0, "A-Perm"),
(1, 5, 5, 0, "UF Adjacent Swap"),
(2, 5, 6, 0, "Swap UB/DB"),
(3, 5, 7, 0, "U/D Diagonal Swap");

INSERT INTO algdb_algmem(alg_id, group_id, case_id) VALUES
(1, 1, 1),
(2, 1, 2),
(8, 1, 3),
(1, 2, 1),
(2, 2, 2),
(8, 2, 3),
(1, 3, 1),
(2, 3, 2),
(3, 4, 1),
(4, 4, 1),
(3, 5, 1),
(5, 5, 2),
(6, 5, 3);

INSERT INTO algdb_tags(mem_id, tag) VALUES
(1, "2-gen"),
(2, "2-gen"),
(4, "2-gen"),
(5, "2-gen"),
(1, "nak"),
(12, "nak"),
(1, "erk"),
(3, "erk");


