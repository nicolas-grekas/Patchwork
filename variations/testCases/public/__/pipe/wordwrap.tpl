<!-- SET a$test -->
(© wikipedia) Yosemite National Park is a national park largely in Mariposa County, and Tuolumne County, California, United States. The park covers an area of 1,189 mi² (3,081 km²) and reaches across the western slopes of the Sierra Nevada mountain chain.
<!-- END:SET -->

<!-- SET a$test -->
{a$test|wordwrap:30}
<!-- END:SET -->

<!-- SET a$expect -->
(© wikipedia) Yosemite
National Park is a national
park largely in Mariposa
County, and Tuolumne County,
California, United States. The
park covers an area of 1,189
mi² (3,081 km²) and reaches
across the western slopes of
the Sierra Nevada mountain
chain.
<!-- END:SET -->

wordwrap: <!-- IF a$test == a$expect -->ok<!-- ELSE -->!<!-- END:IF -->
