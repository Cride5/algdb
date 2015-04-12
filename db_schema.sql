/** Base table for storing algs
All algs must be unique */
CREATE TABLE algdb_algs(
	alg_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	moves VARCHAR(100) NOT NULL,
	stm TINYINT UNSIGNED NOT NULL,
	htm TINYINT UNSIGNED NOT NULL,
	qtm TINYINT UNSIGNED NOT NULL,
	gen TINYINT UNSIGNED NOT NULL,
	PRIMARY KEY(alg_id),
	UNIQUE KEY(moves));


/** Base table for storing algorithm groups
Group name colissions are acceptable,
so long as they are for different puzzles.
If a groups is 'closed' it means it is not
possible to add new cases to it */
CREATE TABLE algdb_groups(
	group_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	group_name VARCHAR(20) NOT NULL,
	puzzle_id INT UNSIGNED NOT NULL,
	is_closed BOOL NOT NULL DEFAULT 0,
	PRIMARY KEY(group_id),
	UNIQUE KEY(group_name, puzzle_id));


/** Base table for storing specific cases within groups
For each case there is an associated alg, which allows 
any alg to be identified as belonging to it (within the context of that group)
the state brought about by the alg is also stored to facilate state searches
Cases may optionally be assigned names */
CREATE TABLE algdb_cases(
	case_id INT UNSIGNED NOT NULL,
	group_id INT UNSIGNED NOT NULL,
	ref_alg INT UNSIGNED NOT NULL,
	state INT UNSIGNED NOT NULL,
	case_name VARCHAR(20),
	PRIMARY KEY(case_id, group_id),
	FOREIGN KEY(ref_alg) REFERENCES algs(alg_id),
	FOREIGN KEY(group_id) REFERENCES groups(group_id));


/** Membership of algs to groups 
One alg may be in a number of groups,
One group has many algs
An alg/group tuple, may or may not belong to a single case */
CREATE TABLE algdb_algmem(
	mem_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	alg_id INT UNSIGNED NOT NULL,
	group_id INT UNSIGNED NOT NULL,
	case_id INT UNSIGNED,
	PRIMARY KEY(mem_id),
	UNIQUE KEY(alg_id, group_id),
	FOREIGN KEY(case_id) REFERENCES cases(case_id),
	FOREIGN KEY(group_id) REFERENCES groups(group_id),
	FOREIGN KEY(alg_id) REFERENCES algs(alg_id));


/** Stores all tags associeated with each
alg in a particular group. For the same
alg in a different group, differnt tags will apply */
CREATE TABLE algdb_tags(
	mem_id INT UNSIGNED NOT NULL,
	tag VARCHAR(20) NOT NULL,
	PRIMARY KEY(mem_id, tag),
	FOREIGN KEY (mem_id) REFERENCES algmem(mem_id));


/*	http://www.jaapsch.net/puzzles/compindx.htm
	For orientations you need to store v^(n-1) states where the orientation of the nth piece is dictated by the other n-1.
	For permutations you need to store n! states, or n!/2 where even parity must exist.
	
	Corner State:
	Orientation = 3^(7) = 2187 ~= 11.09 = 12 bits
	Permutation = 8!/2 = 20160 ~= 14.30 = 15 bits
	Total: 27 bits (26 bits possible)
	
	Edge State:
	Orientation: 2^11 = 11 bits
	Permutation: 12!/2 = 239500800 ~= 27.84 = 28 bits
	Total: 39 bits

	Dedge State:
	Orientation: 2^23 = 23 bits
	Permutation: 24! ~= 79.03 bits = 80 bits
	Total: 103
	
	For a 2x2x2 Cube
	Total: 27 bits (4 bytes)
	
	For a 3x3x3 Cube
	Corners: 26 bits
	Edges: 39 bits
	Total: 66 bits (9 bytes)
	
	For a 4x4x4 Cube
	Corners = 27 bits
	Dedges = 103 bits
	Centres = 24!/4!^6 ~= 51.52 bits = 52 bits
	Total Simplified: 179 bits = 23 bytes
	Total Optimal: ~152.37 = 153 bits = 20 bytes
	
	For a 5x5x5 Cube
	Corners = 27 bits
	Outer Dedges = 103 bits
	Inner Edges = 39 bits
	Centres = (24!/4!^6)^2 ~= 103.06 = 104 bits
	Total Simplified: 273 bits ~= 34.15 = 35 bytes
	Total Optimal: ~247.32 = 248 bits = 31 bytes
	
	For a 7x7x7 Cube
	Total Optimal: ~532.47 = 533 bits = 67 bytes
	
*/

