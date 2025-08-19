<?php declare(strict_types=1);

namespace Limepie;

class ICal extends \ICal\ICal
{
    public function getEvents(\Datetime $start = null, \Datetime $end = null)
    {
        $events1 = $this->events();

        if ($start || $end) {
            $events = [];

            foreach ($events1 as $event) {
                $date = new \Datetime($event->dtstart);

                if ($date >= $start && $date <= $end) {
                    $events[] = $event;
                }
            }
        } else {
            $events = $events1;
        }
        \usort($events, function($a, $b) {
            return \strcmp($a->dtstart, $b->dtstart);
        });

        $return = [];

        foreach ($events as $event) {
            $return[(new \Datetime($event->dtstart))->format('Y-m-d')] = $event->summary;
        }

        return $return;
    }
}
