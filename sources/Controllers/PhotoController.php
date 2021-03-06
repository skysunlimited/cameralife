<?php
namespace CameraLife\Controllers;

use CameraLife\Views as Views;
use CameraLife\Models as Models;

/**
 * Displays the Folder page
 * @author William Entriken <cameralife@phor.net>
 * @copyright 2014 William Entriken
 * @access public
 */
class PhotoController extends HtmlController
{
    private $model;

    public function __construct($modelId)
    {
        parent::__construct();

        if (!Models\Photo::photoExists(intval($modelId))) {
            header("HTTP/1.0 404 Not Found");
            throw new \Exception('Photo #' . intval($modelId) . ' not found.');
        }

        $this->model = Models\Photo::getPhotoWithID($modelId);
        $this->title = $this->model->get('description');
        $this->icon = 'photo';
        $this->url = self::getUrlForID($this->model->id); //todo: done by parent?

        $this->imageType = 'image/jpeg';
        $this->image = $this->model->getMediaURL('thumbnail');
        $this->imageType = 'image/jpeg';
        $this->imageWidth = $this->model->get('tn_width');
        $this->imageHeight = $this->model->get('tn_height');
    }

    public function handleGet($get, $post, $files, $cookies)
    {
        // todo, get PREV and NEXT links from photo and use meta prev/next in HTML theme header

        $this->htmlBareHeader($cookies);
        
        $view = new Views\BackgroundBlurView;
        $view->imageURL = $this->model->getMediaURL('thumbnail');
        $view->render();

        
        $view = new Views\PhotoView;
        $view->photo = $this->model;
        $view->currentUser = Models\User::currentUser($cookies);
        $view->openGraphObject = $this;
        if (isset($get['referrer'])) {
            $view->referrer = $get['referrer'];
        } elseif (isset($_SERVER['HTTP_REFERER'])) {
            $view->referrer = $_SERVER['HTTP_REFERER'];
        }

        if ($this->model->get('status') != 0) {
            if (Models\User::currentUser($cookies)->authorizationLevel < 5) {
                throw new \Exception('This file has been flagged or marked private');
            }
        }
        $this->model->set('hits', $this->model->get('hits') + 1);

		$context = $this->model->getFolder();
		$view->contextUrl = FolderController::getUrlForID($context->id);
        $view->render();


		$photoNext = $this->model->getNext();
		$photoPrev = $this->model->getPrevious();
		$context = $this->model->getFolder();
		
		
        /* Render footer */
        $this->footerJavascript = "
outUrl = '".(FolderController::getUrlForID($context->id))."'
function goOut() {
	$('#mainPic').animate({
      opacity: 'hide'
    }, 'medium', 'easeOutQuad', function() {
      $('#mainPic').remove();
      window.location.href = outUrl
    });
}			

$(document).keyup(function(e) {
	if (e.which == 27) goOut();
});

var hammertime = new Hammer($('#mainPic')[0]);
$.easing.easeInQuad = function (x, t, b, c, d) {
        return c*(t/=d)*t + b;
    };
$.easing.easeOutQuad = function (x, t, b, c, d) {
        return -c *(t/=d)*(t-2) + b;
};
		
nextUrl = '".($photoNext ? self::getUrlForID($photoNext->id) : '')."'
function goNext() {
	if (nextUrl) {		
		$('#mainPic').animate({
	      opacity: 'hide',
	      left: '-200px'
	    }, 'medium', 'easeOutQuad', function() {
	      $('#mainPic').remove();
	      window.location.href = nextUrl
	    });
	} else {
		$('#mainPic').animate({
	      left: '-200px'
	    }, 'fast', 'easeOutQuad', function() {
			$('#mainPic').animate({
		      left: '0'
		    }, 'fast', 'easeInQuad');
	    });		
	}
}			
hammertime.on('swipeleft', goNext);
$(document).keyup(function(e) {
	if (e.which == 39) goNext();
});
		
prevUrl = '".($photoPrev ? self::getUrlForID($photoPrev->id) : '')."'
function goPrev() {
	if (prevUrl) {		
		$('#mainPic').animate({
	      opacity: 'hide',
	      left: '200px'
	    }, 'medium', 'easeOutQuad', function() {
	      $('#mainPic').remove();
	      window.location.href = prevUrl
	    });
	} else {
		$('#mainPic').animate({
	      left: '200px'
	    }, 'fast', 'easeOutQuad', function() {
			$('#mainPic').animate({
		      left: '0'
		    }, 'fast', 'easeInQuad');
	    });		
	}
}			
hammertime.on('swiperight', goPrev);
$(document).keyup(function(e) {
	if (e.which == 37) goPrev();
});
	    ";

        $this->htmlBareFooter();
    }

    public function handlePost($get, $post, $files, $cookies)
    {
        $currentUser = Models\User::currentUser($cookies);

        switch ($post['action']) {
            case 'favorite':
                $this->model->favoriteByUser($currentUser);
                break;
            case 'unfavorite':
                $this->model->unfavoriteByUser($currentUser);
                break;
        }

        parent::handlePost($get, $post, $files, $cookies);
    }
}
