<?php

/**
 * Stats class.
 * get stat information about the whole photo collection
 * @author Will Entriken <cameralife@phor.net>
 * @copyright Copyright (c) 2001-2009 Will Entriken
 * @access public
 */
class Stats
{
    public $counts;

    public function __construct()
    {
        $this->counts = array();
    }

    public function getCounts()
    {
        global $cameralife;

        $this->counts['albums'] = $cameralife->database->SelectOne('albums', 'COUNT(*)');
        $this->counts['topics'] = $cameralife->database->SelectOne('albums', 'COUNT(DISTINCT topic)');
        $this->counts['photos'] = $cameralife->database->SelectOne('photos', 'COUNT(*)');
        $this->counts['pixels'] = $cameralife->database->SelectOne('photos', 'SUM(width*height)');
        $this->counts['albumhits'] = $cameralife->database->SelectOne('albums', 'SUM(hits)');
        $this->counts['photohits'] = $cameralife->database->SelectOne('photos', 'SUM(hits)');
        $this->counts['maxphotohits'] = $cameralife->database->SelectOne('photos', 'MAX(hits)');
        $this->counts['maxalbumhits'] = $cameralife->database->SelectOne('albums', 'MAX(hits)');
        $this->counts['daysonline'] = floor((time() - strtotime($cameralife->getPref('sitedate'))) / 86400);

        return $this->counts;
    }

    public function getPopularPhotos()
    {
        global $cameralife;

        $popularPhotos = array();
        $query = $cameralife->database->Select('photos', 'id', null, 'ORDER BY hits DESC limit 5');
        while ($photo = $query->FetchAssoc()) {
            $popularPhotos[] = new Photo($photo['id']);
        }

        return $popularPhotos;
    }

    public function getPopularAlbums()
    {
        global $cameralife;

        $popular_albums = array();
        $query = $cameralife->database->Select('albums', 'id', null, 'ORDER BY hits DESC limit 5');
        while ($album = $query->FetchAssoc()) {
            $popular_albums[] = new Album($album['id']);
        }

        return $popular_albums;
    }

    public function getFunFacts()
    {
        if (empty($this->counts)) {
            $this->getCounts();
        }

        $funfacts[] = 'If these photos were taken with a film camera, they would have used <strong>' .
            (round($this->counts['photos'] / 24, 0)) . '</strong> rolls of film.';
        $funfacts[] = 'If the photos were layed on a football field, they would go up to the ' .
            '<strong>' . (round($this->counts['pixels'] / 358318080, 2)) . '</strong> yard line.';
        # 358318080 = 160ft * 1 yd * 3ft/yd * 144 in^2/ft^2 * 5184 px^2/in^2
        $funfacts[] = 'If the photo pixels were layed 1-wide, they would circle ' .
            '<strong>' . (round($this->counts['pixels'] / 1135990288, 2)) . '%</strong> of the world.';
        # 1135990288 = 3963.21mi * 2pi * 1760 yd/mi * 36 in/yd * 72 px/in / 100%
        $funfacts[] = 'If I had a nickel for every time someone looked at a picture here, I would have ' .
            '<strong>$' . (floor($this->counts['photohits'] / 20)) . '</strong>.';
        $funfacts[] = 'There have been an average of ' .
            '<strong>' . (round(
                $this->counts['photos'] / ($this->counts['daysonline'] + 1),
                3
            )) . '</strong> photos posted every day.';
        $funfacts[] = 'If you printed these photos and stacked them, they would be ' .
            '<strong>' . (round($this->counts['photos'] / 60, 2)) . '</strong> inches high.';
        $funfacts[] = 'It would take ' .
            '<strong>' . (round($this->counts['photos'] / 350, 0)) . ' shoeboxes</strong> to store all these photos.';
        $funfacts[] = 'Printing these photos on an inkjet printer would use ' .
            '<strong>' . (round($this->counts['photos'] / 11, 0)) . '</strong> cartridges costing ' .
            '<strong>$' . (round($this->counts['photos'] / 11 * 13, 0)) . '</strong> retail.';
        # http://www.epinions.com/content_141398871684
        $funfacts[] = 'Printing these photos with the leading online print service would cost ' .
            '<strong>$' . (round($this->counts['photos'] * 0.15, 0)) . '</strong>.';
        # http://www.shutterfly.com/help/pop/pricing.jsp#volume
        $funfacts[] = 'Putting all these photos on your refrigerator will require ' .
            '<strong>' . (round($this->counts['photos'] / 64, 0)) . ' refrigerators</strong>.';
        # Model General Electric GTS18FBSWW
        $funfacts[] = 'Postage for mailing a photo here to each of your friends (like you have that many) will cost ' .
            '<strong>$' . (round($this->counts['photos'] * 0.44, 2)) . '</strong>.';
        # http://www.usps.com/prices/welcome.htm

        return $funfacts;
    }
}
