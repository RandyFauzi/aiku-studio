<?php

// Upload cutoffs are distinct from the recording/charging sessions in the work planner.
// The end time belongs to the preceding shift; the following shift starts strictly after it.
return [
    'start' => '09:00',
    'shift_1_end' => '13:00',
    'shift_2_end' => '19:00',
    'shift_3_end' => '23:00',
    'overnight_end' => '08:00',
];
