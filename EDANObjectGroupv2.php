<?php

/*
 * PHP classes to implement API calls for the Edan Application "Object Group Management Tool" (OGMT).
 * Non-Drupal specific.
 * These classes can be used within Drupal modules by including this and supporting PHP files in the module includes.
 *
 * Supports API calls for EDAN 1.0:
 * http://edandev.si.edu/applications/#api-_
 *
 * Source at https://github.com/rbartlettquotient/EDANObjectGroupv2
 *
 * 2015-02-01
 */

namespace EDAN\OGMT {

  require_once('EDANInterface.php');

  class ObjectGroups extends \EDAN\EDANBase {

    public $objectGroups;
    public $total;
    public $start;
    public $rows;
    public $sort;
    public $sortDir;
    public $featured;
    public $published; // true or false
    public $deleted; // true or false
    public $groupType;
    public $adminView; // true or false

    public function __construct( $edan_connection = NULL, $withChildren = false, $start = NULL, $rows = NULL, $sort = NULL, $sortDir = NULL,
                                 $featured = NULL, $published = NULL, $deleted = NULL, $groupType = NULL, $adminView = true) {

      $this->total = $this->rows = $this->sort = $this->sortDir = $this->featured = $this->groupType = NULL;
      $this->start = 1;
      $this->objectGroups = array();
      $this->published = true;
      $this->deleted = false;
      $this->adminView = (false == $adminView) ? false : true;

        //@todo: test params, e.g. groupType

      if(NULL !== $edan_connection && is_object($edan_connection)) {
        $this->edan_connection = $edan_connection;
      }

      $this->load($withChildren, $start, $rows, $sort, $sortDir, $featured, $published, $deleted, $groupType, $adminView);

    }

    public function load($withChildren = false, $start = NULL, $rows = NULL, $sort = NULL, $sortDir = NULL,
                                      $featured = NULL, $published = NULL, $deleted = NULL, $groupType = NULL,
                                      $adminView = true) {

      $this->errors = array();

      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot load Object Groups. No EDAN connection set. loadObjectGroups.';
        return false;
      }

      //@todo check parameters for acceptable values
      // is there a cap for $rows?
      // $start must be > 0 ?
      // groupType?

      if(NULL !== $sortDir && $sortDir !== 'asc' && $sortDir !== 'desc') {
        $this->errors[] = "Sort direction must be 'asc' or 'desc'.";
        return false;
      }
      if(NULL !== $featured && $featured != 0 && $featured != 1) {
        $this->errors[] = "Featured must be 0 or 1.";
        return false;
      }
      // $published must be true or false
      if(NULL !== $published && $published !== true && $published !== false) {
        $this->errors[] = "Published must be true or false.";
        return false;
      }

      // $deleted must be true or false
      if(NULL !== $deleted && $deleted !== true && $deleted !== false) {
        $this->errors[] = "Deleted must be true or false.";
        return false;
      }

      $params = array();
      if(NULL !== $start) {
        $this->start = $start;
        $params['start'] = $start;
      }
      if(NULL !== $rows) {
        $this->rows = $rows;
        $params['rows'] = $rows;
      }
      if(NULL !== $sort) {
        $this->sort = $sort;
        $params['sort'] = $sort;
      }
      if(NULL !== $sortDir) {
        $this->sortDir = $sortDir;
        $params['sortDir'] = $sortDir;
      }
      if(NULL !== $featured) {
        $this->featured = $featured;
        $params['featured'] = $featured;
      }
      if(NULL !== $deleted) {
        $this->deleted = $deleted;
        if($this->deleted == true) {
          $params['published'] = -1;
        }
      }
      else {
        if(NULL !== $published) {
          $this->published = $published;
          if($published == 1) {
            $params['published'] = 0; // in this version of the API, set published to zero if it is true
          }
          else {
            $params['published'] = 1; // set to 1 for un-published
          }
        }
      }
      if(NULL !== $groupType) {
        $this->groupType = $groupType;
        $params['groupType'] = $groupType;
      }

      $service = 'ogmt/v1.0/adminogmt/getObjectGroups.htm';
      if(false == $adminView) {
        $service = 'ogmt/v1.0/ogmt/getObjectGroups.htm';
      }

      // call the API to get the ObjectGroups data
      $got_objects = $this->edan_connection->callEDAN($service, $params);
      if(!$got_objects) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Unable to retrieve object groups from EDAN.";
        return false;
      }

      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();
      $this->results_json = $this->edan_connection->getResultsJSON();

      if(NULL !== $this->results_json) {
        // set this object's properties and object list, pages and their object lists

        if(array_key_exists('total', $this->results_json)) {
          $this->total = $this->results_json['total'];
        }

        // update these fields with the values given by the API
        if(array_key_exists('start', $this->results_json)) {
          $this->start = $this->results_json['start'];
        }
        if(array_key_exists('rows', $this->results_json)) {
          $this->rows = $this->results_json['rows'];
        }
        if(array_key_exists('sort', $this->results_json)) {
          $this->sort = $this->results_json['sort'];
        }
        if(array_key_exists('sortDir', $this->results_json)) {
          $this->sortDir = $this->results_json['sortDir'];
        }

        if(array_key_exists('objectGroups', $this->results_json)) {

          foreach($this->results_json['objectGroups'] as $key => $arr) {

            $new_obj = new ObjectGroup($this->edan_connection);
            $new_obj->loadFromArray($arr, $withChildren);
            $this->objectGroups[$key] = $new_obj;

          } // if we have some objects

        } // for each object

      } // if we have results_json

      return true;

    } // load the object groups

  } // ObjectGroups

  class ObjectGroup extends \EDAN\EDANBase {

    protected $objectGroupId;
    protected $deleted; // true or false

    public $objectGroupPages;
    public $selectedPageId;
    public $defaultPageId;
    public $title;
    public $body;
    public $objectGroupImageUri;
    public $listTitle;
    public $uri;
    public $groupType;
    public $keywords;
    public $published; // true or false
    public $featured;
    public $disableMenu;
    public $settings;
    public $tokenId;
    public $objectList;
    public $adminView; // true or false

    public function __construct( $edan_connection = NULL, $objectGroupId = NULL, $adminView = true) {

      // set defaults
      $this->objectGroupId = NULL;

      $this->results_raw = NULL;
      $this->results_json = NULL;
      $this->results_info = NULL;
      $this->errors = array();

      $this->edan_connection = NULL;
      $this->objectGroupPages = array();
      $this->objectList = NULL;

      $this->selectedPageId = '';
      $this->defaultPageId = '';
      $this->title = '';
      $this->listTitle = '';
      $this->body = '';
      $this->objectGroupImageUri = '';
      $this->uri = '';
      $this->groupType = NULL;
      $this->keywords = '';
      $this->published = true;
      $this->deleted = false;
      $this->featured = false;
      $this->disableMenu = false;
      $this->tokenId = '';
      $this->settings = array();

      $this->adminView = (false == $adminView) ? false : true;

      if(NULL !== $edan_connection && is_object($edan_connection)) {
        $this->edan_connection = $edan_connection;
      }

      if(null == $objectGroupId) {
        // this is a new object; don't talk to the API yet
      }
      else {
        // hit the API to get this object group
        if(NULL == $this->edan_connection) {
          $this->errors[] = "No EDAN connection. Cannot load object group with id '"
            . $objectGroupId . "'.";
        }
        else { // we have an EDAN connection
          if(!$this->load($objectGroupId, $adminView)) {
            $this->errors[] = 'Object Group was not loaded.';
          }
        }

      } // if we have an objectGroupId

    }

    // expose the app id for the connection object
    public function getAppId() {
      $app_id = '';
      if(NULL !== $this->edan_connection) {
        if(NULL !== $this->edan_connection->getAppId()) {
          $app_id = $this->edan_connection->getAppId();
        }
      }

      return $app_id;
    }

    public function getErrors() {
      return $this->errors;
    }

    public function getObjectGroupId() {
      return $this->objectGroupId;
    }

    public function setObjectGroupId($objectGroupId) {
      $this->objectGroupId = $objectGroupId;
    }

    public function load($objectGroupId, $adminView = true) {
      //@todo test $objectGroupId for expected pattern?

      $this->errors = array();

      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot load Object Group. No EDAN connection set. load().';
        return false;
      }

      $this->objectGroupId = $objectGroupId;
      $service = 'ogmt/v1.0/adminogmt/getObjectGroup.htm';
      if(false == $adminView) {
        $service = 'ogmt/v1.0/ogmt/getObjectGroup.htm';
      }
      $params = array(
        'objectGroupId' => $objectGroupId,
      );

      // load the object group if we have an objectGroupId
      // call the API to get the ObjectGroup data
      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not load object group. load()";
        return false;
      }

      if(isset($this->results_json) && (array_key_exists('error', $this->results_json )) ) {
        if(is_array($this->results_json['error'])) {
          $this->errors[] = $this->results_json['error'];
        }
        else {
          $this->errors[] = $this->results_json['error'];
        }
        return false;
      }

      if(NULL !== $this->results_json) {
        // set this object's properties and object list, pages and their object lists
        //      $this->objectGroupId = $jsonobj->response->objectGroupId;
        // load stuff from JSON into structures we are expecting
        $this->loadFromArray($this->results_json);

      } // if we have results_json

      return true;

    } // load the object group by calling the API for data

    public function loadByUri($uri, $adminView = true) {
      //@todo test $uri for expected pattern

      $this->errors = array();

      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot load Object Group. No EDAN connection set. load().';
        return false;
      }

      $this->uri = $uri;
      $service = 'ogmt/v1.0/adminogmt/getObjectGroup.htm';
      if(false == $adminView) {
        $service = 'ogmt/v1.0/ogmt/getObjectGroup.htm';
      }
      $params = array(
        'objectGroupUrl' => $uri,
      );

      // load the object group if we have an objectGroupId
      // call the API to get the ObjectGroup data
      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not load object group. load()";
        return false;
      }

      if(isset($this->results_json) && (array_key_exists('error', $this->results_json )) ) {
        if(is_array($this->results_json['error'])) {
          $this->errors[] = $this->results_json['error'];
        }
        else {
          $this->errors[] = $this->results_json['error'];
        }
        return false;
      }

      if(NULL !== $this->results_json) {
        // set this object's properties and object list, pages and their object lists
        //      $this->objectGroupId = $jsonobj->response->objectGroupId;
        // load stuff from JSON into structures we are expecting
        $this->loadFromArray($this->results_json);
      } // if we have results_json

      return true;

    } // load the object group by calling the API for data

    public function loadFromArray($arr, $withChildren = true) {

      if(array_key_exists('objectGroupId', $arr)) {
        $this->objectGroupId = $arr['objectGroupId'];
      }
      if(array_key_exists('title', $arr)) {
        $this->title = $arr['title'];
      }
      if(array_key_exists('listTitle', $arr)) {
        $this->listTitle = $arr['listTitle'];
      }
      if(array_key_exists('description', $arr)) {
        $this->body = $arr['description'];
      }
      if(array_key_exists('url', $arr)) {
        $this->uri = $arr['url'];
      }
      if(array_key_exists('groupType', $arr)) {
        $this->groupType = $arr['groupType'];
      }
      if(array_key_exists('keywords', $arr)) {
        $this->keywords = $arr['keywords'];
      }
      // incoming published value is: -1 for deleted, 1 for published, 0 for un-published
      if(array_key_exists('published', $arr)) {
        if($arr['published'] == -1) {
          $this->deleted = true;
          $this->published = false;
        }
        elseif($arr['published'] == 1) { // published = 1 means un-published in this version of the API
          $this->published = false;
        }
        else {
          $this->published = true;
        }
        // the default for published is true
      }
      if(array_key_exists('featured', $arr)) {
        $this->featured = $arr['featured'] == 1 ? true : false;
      }

      if(array_key_exists('page',$arr) ) {
        $this->page = $arr['page'];
      }

      if(array_key_exists('defaultPage',$arr) ) {
        $this->defaultPageId = $arr['defaultPage'];
      }
      if(array_key_exists('defaultPageId', $arr) ) {
        $this->defaultPageId = $arr['defaultPageId'];
      }

      // settings - anything else in this list of values?
      $this->disableMenu = 0;
      if(array_key_exists('settings',$arr) && array_key_exists('disableMenu', $arr['settings'])) {
        $this->settings = $arr['settings'];
        $this->disableMenu = $this->settings['disableMenu']; // controls whether the menu appears on the object group pages
      }

      // objectGroupImageUri - "feature": { "type": "image", "url": "" }
      if(array_key_exists('feature', $arr)) {
        $feature_array = $arr['feature'];
        if(array_key_exists('url', $feature_array)) {
          $this->objectGroupImageUri = $feature_array['url'];
        }
      } // if we have a feature

      if($withChildren) {
        /*
         * objects - list of items, or a list of search params
         * list of items looks like:
         * "objects": { "listType": 0, "size": 1, "listName": "QUOTIENTTEST:dpt-1445611947110-1445638605523-0:0", "items": [ "siris_sil_960883" ] }
         */
        if(array_key_exists('objects', $arr)) {
          $obj_list = new ObjectList($arr['objects'], $this->edan_connection, $this->objectGroupId);
          if(is_object($obj_list)) {
            $this->objectList = $obj_list;
          }
        } // object listing

        // array of key-value pairs representing pages
        /* looks like this:
         * "menu": [ { "id": "dpt-1445611947110-1445638793497-0", "url": "jk7rgecnejhzkug9zxiwgf9xe79hzv0hck5ams8e", "title": "jK7RGECnEJHzkug9zxIWGF9XE79hZV0Hck5amS8E" } ]
        */
        if(array_key_exists('menu', $arr)) {
          foreach($arr['menu'] as $key => $p) {
            $newp = new ObjectGroupPage(NULL, $this->edan_connection, $this->objectGroupId, $p['id']);
            /*
            $newp->objectGroupId = $this->objectGroupId;
            if(array_key_exists('url', $p)) {
              $newp->uri = $p['url'];
            }
            if(array_key_exists('title', $p)) {
              $newp->title = $p['title'];
            }
            if(array_key_exists('id', $p)) {
              $newp->pageId = $p['id'];
            }
            */
            $this->objectGroupPages[$key] = $newp;
          } // for each menu object, create a page and add to this object group's pages
        } // load pages from menu

      } // if loading the og with children - meaning object listing and pages



    } // load the object group with the values provided in $arr parameter

    public function setDefaultPage($pageId) {
      // note API function call says we can also pass pageArray, a JSON array of the page values

      $this->errors = array();

      if(!isset($this->objectGroupId)) {
        $this->errors[] = "Can't set default page, objectGroupId not set. setDefaultPage()";
        return false;
      }

      $params = array();
      $service = 'ogmt/v1.0/adminogmt/setDefaultPage.htm';

      $params['objectGroupId'] = $this->objectGroupId;
        $params['pageId'] = $pageId;

      // save the object group
      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = 'No object retrieved from EDAN. setDefaultPage()';
        return false;
      }

      if(isset($this->results_json) && array_key_exists('MissingParams', $this->results_json )) {
        $this->errors[] = "Could not set default page. setDefaultPage() " . $this->results_json['MissingParams'];
        return false;
      }

        $this->defaultPageId = $pageId;
      return true;

    } // set the default page for this object group

    public function getMenu() {
      // returns a simple array of the page IDs
      $menu = array();
      foreach($this->objectGroupPages as $key => $pg) {
        $menu[] = $pg->getPageId();
      }
      return $menu;
    }

    public function setMenu($pageIds = NULL) {

      $this->errors = array();

      if(!isset($this->objectGroupId)) {
        $this->errors[] = "Can't set menu, objectGroupId not set. setMenu()";
        return false;
      }

      $params = array();
      $service = 'ogmt/v1.0/adminogmt/setMenu.htm';

      $params['objectGroupId'] = $this->objectGroupId;
      $pages = array();

        if(NULL == $pageIds) {
          // use the current page order
          foreach($this->objectGroupPages as $key => $pg) {
            $pages[] = $pg->getPageId();
          }
        }
        else {
          // use the page order defined in the parameter
          foreach($pageIds as $pgid) {
      foreach($this->objectGroupPages as $key => $pg) {
              if($pg->getPageId() == $pgid) {
                $pages[] = $pg->getPageId();
              }
            }
          }
          // are there any pages not represented in the array? we should set them
          foreach($this->objectGroupPages as $key => $pg) {
            if(!in_array($pg->getPageId(), $pages)) {
        $pages[] = $pg->getPageId();
      }
          }
        }

      if(count($pages) < 1) {
        $this->errors[] = 'Object group has no pages. Cannot set menu. setMenu()';
        return false;
      }
      $params['pageArray'] = json_encode($pages);

        // save the menu
      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = 'No object retrieved from EDAN. setMenu()';
        return false;
      }

      if(isset($this->results_json) && !array_key_exists('message', $this->results_json )) {
        $this->errors[] = "Could not set menu- confirmation message not received. setMenu()";
        return false;
      }

        // if we were able to set the page order successfully, update the page order for this object group
        $tmp = $this->objectGroupPages;
        $this->objectGroupPages = array();
        foreach($pages as $key=> $pageId) {
          foreach($tmp as $k => $page) {
            if($page->getPageId() == $pageId) {
              $this->objectGroupPages[] = $page;
            }
          }
        }

      return true;
    } // save the menu for the object group, in the current order

    public function fileListing() {
        // not used in existing modules
    }

    public function getToken() {

      $this->errors = array();
      $this->tokenId = '';

      // do we have edan?
      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot save Object Group. No EDAN connection set. save().';
        return false;
      }

      $params = array();
      $service = 'content/v1.0/fileupload/tokenRequest.htm';

      // save the object group
      $got_token = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_token) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = 'No token retrieved from EDAN. getToken()';
        return false;
      }

      // if this is a new object group, see if we got the id back
      /*
      {
        "tokenId": "72c2d683-e41a-4de3-81b3-5d0297d1827d",
        "message": "token valid for 5 minutes.."
      }
      */
      if (array_key_exists('tokenId', $this->results_json)) {
        $this->tokenId = $this->results_json['tokenId'];
      }
      else {
        $this->errors[] = 'Unable to get token. No tokenId found in response. getToken()';
        if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
          $this->errors[] = $this->edan_connection['errors'];
          return false;
        }
      }

      return true;

    }

    public function save() {
      // save the current values of this object group using API call

      $this->errors = array();

      // do we have edan?
      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot save Object Group. No EDAN connection set. save().';
        return false;
      }

        //@todo: On save attempt, load object groups with matching uri
      //if count > 0, fail the attempted save

      $params = array();

      $service = 'ogmt/v1.0/adminogmt/editObjectGroup.htm';

      // different endpoint for creating a new Object Group
      if(NULL == $this->objectGroupId) {
        $service = 'ogmt/v1.0/adminogmt/createObjectGroup.htm';
      }
      else {
        $params['objectGroupId'] = $this->objectGroupId;
      }

      $params['title'] = $this->title;
      // not used by edit or create $params['defaultPageId'] = $this->defaultPageId;
      $params['listTitle'] = $this->listTitle;
      $params['url'] = $this->uri;
      $params['description'] = $this->body;
      $params['keywords'] = $this->keywords;
      $groupType = (NULL == $this->groupType) ? -1 : $this->groupType;
      $params['groupType'] = $groupType;
      $publishedValue = (true == $this->published) ? 0 : 1;
      $params['published'] = (true == $this->deleted) ? -1 : ($publishedValue);
      $params['featured'] = $this->featured;

      // objectGroupImageUri - "feature": { "type": "image", "url": "" }
      //@todo ref Slack convo with Andrew 12/17/2015 and his notes from 7/2/2015
      // properties stored in feature include: type, thumbnail, image, alt, html
      $feature_array = array('type' => 'image', 'url' => '');
      if(isset($this->objectGroupImageUri) && strlen($this->objectGroupImageUri) > 0) {
        $feature_array['url'] = $this->objectGroupImageUri;
        $params['feature'] = json_encode($feature_array);
      }

      // settings may have values for: disableMenu
      //@todo disableMenu setting will be deprecated
      /*
      $params['settings'] = json_encode(array('disableMenu' => (int)$this->disableMenu));
      if(false == $this->disableMenu) {
        if(NULL !== $this->objectGroupId) {
          if(NULL !== $this->objectGroupPages && count($this->objectGroupPages) > 0)
          // and if we have some pages
          $this->setMenu();
        }
      }
      */

      // save the object group
      $got_object = $this->edan_connection->callEDAN($service, $params, true);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = 'No object retrieved from EDAN. save()';
        return false;
      }

        // set the id if we got it back
      if (array_key_exists('objectGroupId', $this->results_json)) {
        $this->objectGroupId = $this->results_json['objectGroupId'];
          $this->uri = array_key_exists('url', $this->results_json) ? $this->results_json['url'] : '';
      }
      else {
        $this->errors[] = 'Unable to save Object Group. No ObjectGroupId found in response. save()';
        if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
          $this->errors[] = $this->results_json['error'];
          return false;
        }
      }

      return true;

    } // saves the object group values and the menu (page) order; does not save pages nor object listing

    public function markForDeletion() {

      $this->errors = array();

      // do we have $edan?
      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot delete Object Group. No EDAN connection set. deleteObjectGroup().';
        return false;
      }

      if(NULL == $this->objectGroupId) {
        $this->errors[] = 'Cannot delete Object Group. Object Group ID not set. deleteObjectGroup().';
        return false;
      }

      $params = array();
      $service = 'ogmt/v1.0/adminogmt/releaseObjectGroup.htm';
      $params['objectGroupId'] = $this->objectGroupId;

      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not delete object group. deleteObjectGroup()";
        return false;
      }

      if(!array_key_exists('objectGroupId', $this->results_json) && !array_key_exists('objectsReleased', $this->results_json)) {
        $this->errors[] = "The API call may have successfully executed but the API did not report the objectGroupId. markForDeletion().";
        return false;
      }
      if(array_key_exists('objectGroupId', $this->results_json) && $this->results_json['objectGroupId'] != $this->objectGroupId) {
        $this->errors[] = "The API call may have successfully executed but the API returned an objectGroupId which is different from the parameter: " . $this->results_json['objectGroupId'] . ".";
        return false;
      }
      if(array_key_exists('objectsReleased', $this->results_json) && $this->results_json['objectsReleased'] < 1) {
        $this->errors[] = "The API call may have successfully executed but the API indicated that no objects were released: " . $this->results_json['objectsReleased'] . ".";
        return false;
      }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
        return false;
      }

      $this->deleted = true;

      return true;

    } // mark object group for deletion

    public function isDeleted() {
      return $this->deleted;
    }

    public function savePage(ObjectGroupPage $page) {
      // saves (adds or edits) the page details for the ObjectGroupPage at index $pageIndex of $this->objectGroupPages
      // saves the menu (setMenu call)

      $this->errors = array();

      $is_new = (NULL == $page->getPageId()) ? true : false;
      $page->setObjectGroupId($this->objectGroupId);
      if($page->save()) {
        // update pages for this group
        if($is_new) {
          $this->objectGroupPages[] = $page;
        }
      }
      else {
        $this->errors[] = $page->errors;
        return false;
      }

      return true;
    } // save a new or existing page to this object group

    public function setPageOrder() {

      $this->errors = array();

      if(NULL == $this->objectGroupPages) {
        $this->errors[] = "This object group has no pages. Cannot set page order. setPageOrder()";
        return false;
      }

      return $this->setMenu();

    } // set the object group's page order based on the $pageIds array parameter

    public function deletePage($pageId) {
      // delete the page using API call

      $this->errors = array();

      // do we have edan?
      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot delete Object Group Page. No EDAN connection set. deletePage().';
        return false;
      }

      $params = array();
      $service = 'ogmt/v1.0/adminogmt/releasePage.htm';

      $params['objectGroupId'] = $this->objectGroupId;
      $params['pageId'] = $pageId;

      // delete the page
      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = 'Page could not be deleted from EDAN. deletePage()';
        return false;
      }

      if(isset($this->results_json) && array_key_exists('MissingParams', $this->results_json )) {
        $this->errors[] = "Could not delete page using releasePage. deletePage() " . $this->results_json['MissingParams'];
        return false;
      }
      if(isset($this->results_json) && array_key_exists('releaseError', $this->results_json )) {
        $this->errors[] = "Some parameters are missing when calling releasePage. deletePage()";
        return false;
      }

      // should we make sure results_json contains the object group?

        // delete the page from this object group
        foreach($this->objectGroupPages as $k => $page) {
          if($page->getPageId() == $pageId) {
            unset($this->objectGroupPages[$k]);
          }
        }
      return true;

    } // delete the specified page from the object group

  } // ObjectGroup

  class ObjectGroupPage extends \EDAN\EDANBase {

    protected $objectGroupId;
    protected $pageId;

    public $edan_connection;
    public $title;
    public $listTitle;
    public $feature;
    public $content;
    public $pageImageUri;
    public $uri;
    public $settings;
    public $disableObjectListing;
    public $objectList;

    // given in menu as:
    //{ "id": "dpt-1445611947110-1445638793497-0", "url": "jk7rgecnejhzkug9zxiwgf9xe79hzv0hck5ams8e", "title": "jK7RGECnEJHzkug9zxIWGF9XE79hZV0Hck5amS8E" }
    public function __construct( $arr = NULL, $edan_connection = NULL, $objectGroupId = NULL, $pageId = NULL ) {

      $this->objectGroupId = NULL;
      $this->pageId = NULL;
      $this->edan_connection = NULL;
      $this->title = $this->content = $this->feature = $this->pageImageUri = $this->listTitle = $this->uri = '';
      $this->settings = NULL;
      $this->disableObjectListing = false;
      $this->objectList = NULL;

      if(NULL !== $edan_connection) {
        $this->edan_connection = $edan_connection;
      }
      //@todo test objectGroupId, pageId for malicious stuff
      if(NULL !== $objectGroupId) {
        $this->objectGroupId = $objectGroupId;
      }
      if(NULL !== $arr && is_array($arr)) {
        $this->loadFromArray($arr);
      }
      elseif(NULL !== $edan_connection && NULL !== $objectGroupId && NULL !== $pageId) {
        //@todo test objectGroupId, pageId for malicious stuff
        $this->edan_connection = $edan_connection;
        $this->load($objectGroupId, $pageId);
      }
    }

    public function getObjectGroupId() {
      return $this->objectGroupId;
    }

    public function getPageId() {
      return $this->pageId;
    }

    public function setObjectGroupId($objectGroupId) {
      //@todo some checks
      $this->objectGroupId = $objectGroupId;
    }

    public function setPageId($pageId) {
      $this->pageId = $pageId;
    }

    public function load($objectGroupId, $pageId) {

      $this->errors = array();

      if(NULL == $this->edan_connection || !is_object($this->edan_connection)) {
        $this->errors[] = 'Cannot load Object Group Page. No EDAN connection set. _load_page.';
        return false;
      }

      $this->objectGroupId = $objectGroupId;
      $this->pageId = $pageId;
      $service = 'ogmt/v1.0/adminogmt/getObjectGroup.htm';
      $params = array(
        'objectGroupId' => $objectGroupId,
        'pageId' => $pageId,
      );

      // call the API to get the ObjectGroup page data
      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not load object group page. _load_page()";
        return false;
      }

      if(isset($this->results_json) && (array_key_exists('error', $this->results_json )) ) {
        $this->errors[] = $this->results_json['error'];
        return false;
      }

      if(NULL !== $this->results_json && array_key_exists('page', $this->results_json)) {
        // set this page's properties and object list
        // load stuff from JSON into structures we are expecting
          $this->loadFromArray($this->results_json);
      } // if we have results_json
      else {
        $this->errors[] = 'JSON is null or no page data found. load()';
        return false;
      }

      return true;

    } // load the page by calling the API for data

    public function loadFromArray($arr) {
      $this->errors = array();

        if(array_key_exists('objectGroupId', $arr['page'])) {
          $this->objectGroupId = $arr['page']['objectGroupId'];
      }
        if(array_key_exists('pageId', $arr['page'])) {
          $this->pageId =$arr['page']['pageId'];
      }
        if(array_key_exists('title', $arr['page'])) {
          $this->title = $arr['page']['title'];
      }
        if(array_key_exists('listTitle', $arr['page'])) {
          $this->listTitle = $arr['page']['listTitle'];
      }
      // feature actually isn't used right now for Pages
      /*
        if(array_key_exists('feature', $arr['page'])) {
          $this->feature = $arr['page']['feature'];
      }
      */
        if(array_key_exists('content', $arr['page'])) {
          $this->content = $arr['page']['content'];
      }
        if(array_key_exists('url', $arr['page'])) {
          $this->uri = $arr['page']['url'];
      }
      // settings - anything else in this list of values?
        if(array_key_exists('settings', $arr['page']) ) {
          $this->settings = $arr['page']['settings'];
        if(array_key_exists('disableObjects', $this->settings)) {
          $this->disableObjectListing = $this->settings['disableObjects'];
        }
      }

      // we have to make another call to get the object listing metadata for a page
      if(array_key_exists('objects', $arr) ) {
        $obj_list = new ObjectList($arr['objects'], $this->edan_connection, $this->objectGroupId, $this->pageId);
        if(is_object($obj_list)) {
          $this->objectList = $obj_list;
        }
      }
      else {
        $this->objectList = NULL;
      }

    }

    public function save() {
      /*
       * for new page calls createPage
       * to save existing page calls editPage
      */

      $this->errors = array();
      $pageId = NULL;

      $params = array();
      $params['objectGroupId'] = $this->objectGroupId;
      if(isset($this->title)) {
        $params['title'] = $this->title;
      }
      if(isset($this->listTitle)) {
        $params['listTitle'] = $this->listTitle;
      }
      $params['url'] = $this->uri;
      if(isset($this->content)) {
        $params['content'] = $this->content;
      }
      //feature isn't actually used right now for Pages
      /*
      if(isset($this->feature)) {
        $params['feature'] = $this->feature;
      }
      */

      // are there any other settings?
      if(isset($this->disableObjectListing)) {
        $params['settings'] = json_encode(array('disableObjects' => (int)$this->disableObjectListing));
      }

      $service = 'ogmt/v1.0/adminogmt/editPage.htm';
      if(NULL == $this->pageId) {
        // add a page to this group using API call
        $service = 'ogmt/v1.0/adminogmt/createPage.htm';
      }
      else {
        // save changes to an existing page
        $params['pageId'] = $this->pageId;
      }

      $got_object = $this->edan_connection->callEDAN($service, $params, true);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not save page. save()";
        return false;
      }

        if(!array_key_exists('pageId', $this->results_json)) {
          $this->errors[] = "The API call was successfully executed but the API did not return a pageId. save()";
        return false;
      }
        else {
          $this->pageId = $this->results_json['pageId'];
          $this->uri = array_key_exists('url', $this->results_json) ? $this->results_json['url'] : '';
        }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
        return false;
      }

      return true;

    } // save function

  } // class for object group page

  class ObjectList extends \EDAN\EDANBase {

    protected $objectGroupId;
    // $item as used in this class is a string value representing an object; an object id.

    // optional
    protected $pageId;
    public $listType;
    public $listName;
    public $size;
    public $items;
    public $queryTerms; // query terms concatenated with +
    public $queryFacets; // array of facets
    public $settings;

    public function __construct( $arr = NULL, $edan_connection, $objectGroupId = NULL, $pageId = NULL) {

      $this->objectGroupId = $this->pageId = null;
      $this->listName = '';
      $this->listType = 0; // default to hand-picked list of objects
      $this->size = 0;
      $this->items = array();
      $this->queryTerms = NULL;
      $this->queryFacets = array();
      $this->settings = array();
      $this->errors = array();

      if (NULL !== $objectGroupId) {
        $this->objectGroupId = $objectGroupId;
        if(NULL !== $pageId) {
          $this->pageId = $pageId;
        }
          if(NULL !== $edan_connection) {
            $this->edan_connection = $edan_connection;
          }

        if(NULL !== $arr && is_array($arr)) {
          $this->loadFromArray($arr, $objectGroupId, $pageId);
        } // if we have an object
        elseif(NULL !== $edan_connection) {
          // otherwise if we have at least $objectGroupId and a connection we can load the object list using the API call:
          $this->load();
        }

      } // can't do anything without an objectGroupId


    }

    public function getObjectGroupId() {
      return $this->objectGroupId;
    }

    public function getPageId() {
      return $this->pageId;
    }

    public function setObjectGroupId($objectGroupId) {
      //@todo some checks
      $this->objectGroupId = $objectGroupId;
    }

    public function setPageId($pageId) {
      $this->pageId = $pageId;
    }

    public function addItems($items = NULL) {
      // $items can be a single string value or an array of values
      // if $items is not set, this object listing's items will be used

      $this->errors = array();
      if(NULL == $this->objectGroupId) {
        $this->errors[] = 'Object Group Id not set. Could not load Object List. addItems()';
          return false;
      }

        // items should be an array of item ids
      if(NULL !== $items) {
          if(is_array($items)) {
        	$this->items = $items;
      	  }
          else { // treat it like a string
            $this->items[] = $items;
          }
      }

      $bHaveItems = true;
      $bHaveSearch = true;
      $param_items_array = array();
      $param_items = '';

        if(NULL == $this->items
          || (is_array($this->items) && count($this->items) == 0)
          || (!is_array($this->items) && strlen($this->items == 0) )) {
        $bHaveItems = false;
      }
      else {
        if(count($this->items) > 1) {
          $param_items = json_encode($this->items);
        }
        else {
          $param_items = $this->items[0];
        }
      }

        if(NULL == $this->queryTerms || strlen($this->queryTerms) == 0) {
        $bHaveSearch = false;
      }
      else {
        $param_items_array[] = urlencode($this->queryTerms);
        if(count($this->queryFacets) > 0 ) {
          //for item: $fq_string = '"fq": "' . implode(',', $temp) . '"';
          foreach($this->queryFacets as $key => $fq_value) {
            $param_items_array[] = $fq_value;
          }
        }
        $param_items = '["' . implode('","', $param_items_array) . '"]';
      }

        if(false == $bHaveItems && false == $bHaveSearch) {
        $this->errors[] = 'No items provided provided. Could not add items to object list. addItems()';
          return false;
      }

      $service = 'ogmt/v1.0/adminogmt/editObjectListing.htm';
      $params = array();
      $params['action'] = 'add'; //action  (add,remove,move,review,clear) -defaults to review
        $params['objectGroupId'] = $this->objectGroupId;
      $params['listType'] = $this->listType;
      if(isset($this->pageId)) {
        $params['pageId'] = $this->pageId;
      }
      if(count($this->items) > 1 || $bHaveSearch) {
        $params['items'] = $param_items;
      }
      else {
        $params['item'] = $param_items;
      }

      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

        // clear items; we'll re-load them below
        $this->items = array();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not add items to object list. addItems()";
        return false;
      }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
          return false;
      }

        // update the object list
        if(array_key_exists('lists', $this->results_json) && array_key_exists('items', $this->results_json['lists'])) {
          $this->loadFromArray($this->objectGroupId, $this->results_json['lists']);
          //$this->items = $this->results_json['lists']['items'];
        }

        return true;

    } // add one or more items

    public function removeItem($itemId) {

      if(NULL == $this->objectGroupId) {
        $this->errors[] = 'Object Group Id not set. Could not load Object List. load()';
          return false;
      }

      $service = 'ogmt/v1.0/adminogmt/editObjectListing.htm';
      $params = array();
      $params['action'] = 'remove'; //action  (add,remove,move,review,clear) -defaults to review
      $params['objectGroupId'] = $this->objectGroupId;
      if(isset($this->pageId)) {
        $params['pageId'] = $this->pageId;
      }
      $params['item'] = $itemId;

      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not remove item from object list. removeItem()";
        return false;
      }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
          return false;
      }

        // update the object list
        if(is_array($this->results_json)) {
//          && array_key_exists('lists', $this->results_json)) {
//          $this->loadFromArray($this->results_json['lists'], $this->objectGroupId, $this->pageId);
          foreach($this->items as $k => $itm) {
            if($itm == $itemId) {
              unset($this->items[$k]);
            }
          }
        }

        return true;

    } // remove an item from the list

    public function moveItem($itemId, $afterItemId) {

      if(NULL == $this->objectGroupId) {
        $this->errors[] = 'Object Group Id not set. Could not load Object List. load()';
          return false;
      }

      $service = 'ogmt/v1.0/adminogmt/editObjectListing.htm';
      $params = array();
      $params['action'] = 'move'; //action  (add,remove,move,review,clear) -defaults to review
      $params['objectGroupId'] = $this->objectGroupId;
      if(isset($this->pageId)) {
        $params['pageId'] = $this->pageId;
      }
      $params['item'] = $itemId;
      //@todo or items?
      $params['afterId'] = $afterItemId; // $item will be moved after $afterItem in the object list

      /*
        listType -int
        item
        items accepts JSON-formatted array
        action  (add,remove,move,review,clear) -defaults to review
        afterId (after what record_id)--used in move
      */

      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not move item on object list. moveItem()";
        return false;
      }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
          return false;
      }

        // recreate our list so this object list has the objects in the correct order
        if(is_array($this->results_json)
          && array_key_exists('lists', $this->results_json)) {
          $this->loadFromArray($this->results_json['lists']['items'], $this->objectGroupId, $this->pageId);
        }
        return true;

    } // move an item in the list

    public function clear($clearLocalValues = true) {

      if(NULL == $this->objectGroupId) {
            $this->errors[] = 'Object Group Id not set. Could not clear Object List. clear()';
          return false;
      }

      $service = 'ogmt/v1.0/adminogmt/editObjectListing.htm';
      $params = array();
      $params['action'] = 'clear'; //action  (add,remove,move,review,clear) -defaults to review
      $params['objectGroupId'] = $this->objectGroupId;
      if(isset($this->pageId)) {
        $params['pageId'] = $this->pageId;
      }

      $got_object = $this->edan_connection->callEDAN($service, $params);

      if(!($got_object)) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not clear object list. clear()";
        return false;
      }

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
        return false;
      }

      $ok = true;

      if($clearLocalValues) {
        if($this->listType == 1) {
          // if we had a saved search and cleared it, we need to reset the listType to 0
          $ok = $this->modifyType(0);
            if($ok) {
              $this->listType = 0;
            }
        }

        // reset this object listing's values
        $this->listType = 0;
        $this->size = 0;
        $this->queryFacets = '';
        $this->queryTerms = '';
        $this->items = array();
        $this->settings = array();
        $this->load();
      }

      return $ok;

    } // clear the object listing

    public function load() {

      if(NULL == $this->edan_connection) {
        $this->errors[] = "No EDAN connection. Cannot load object list.";
          return false;
      }
      if(NULL == $this->objectGroupId) {
        $this->errors[] = 'Object Group Id not set. Could not load Object List. load()';
          return false;
      }

      $service = 'ogmt/v1.0/adminogmt/getObjectListingMetadata.htm';
      $params = array();
      //$params['action'] = 'review'; // action  (add,remove,move,review,clear) -defaults to review
      $params['objectGroupId'] = $this->objectGroupId;
      if(isset($this->pageId)) {
        $params['pageId'] = $this->pageId;
      }

      /*
        listType -int
        item
        items accepts JSON-formatted array
        action  (add,remove,move,review,clear) -defaults to review
        afterId (after what record_id)--used in move
      */
      $this->size = 0;
      $this->items = array();
      $this->queryTerms = NULL;
      $this->queryFacets = array();

      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not load object list. load()";
        return false;
      }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
          return false;
      }

      // get values from $got_object
      if(is_array($this->results_json)
        && (
          array_key_exists('lists', $this->results_json)
          || array_key_exists('items', $this->results_json)
        )
      ) {

        $this->loadFromArray($this->results_json, $this->objectGroupId, $this->pageId);
      }

      return true;

    } // load the object listing by calling the API

    public function loadFromArray($arr, $objectGroupId, $pageId = NULL) {

        if(!is_array($arr)) {
          return;
        }

      if(NULL !== $objectGroupId) {
        $this->objectGroupId = $objectGroupId;
      }
      if(NULL !== $pageId) {
        $this->pageId = $pageId;
      }

        if(array_key_exists('listName', $arr)) {
          $this->listName = $arr['listName'];
        }

      if(array_key_exists('size', $arr)) {
        $this->size = $arr['size'];
      }
      elseif(array_key_exists('numFound', $arr)) {
        $this->size = $arr['numFound'];
      }

      if(array_key_exists('listType', $arr)) {
        $this->listType = $arr['listType'];
      }
      else {
        // if listType isn't set, this might be a saved search or it might be an empty object listing
        if($this->size > 0) {
          $this->listType = 1;
        }
      }

      if(array_key_exists('items', $arr)) {
          $b_content_items = false;

          if(count($arr['items']) > 0 && is_array($arr['items'][0])) {
            // each might have content, type, url
            // get the record id
            foreach($arr['items'] as $item) {
              $record_ID = 0;
              //@todo- check array_key_exists, not isset
              if(isset($item['content']['descriptiveNonRepeating']['record_ID'])) {
                $record_ID = $item['content']['descriptiveNonRepeating']['record_ID'];
                $b_content_items = true;
              }
              else {
                // try to get it from the url
                if(false !== strpos($item['url'], ':')) {
                  $s = explode(':', $item['url']);
                  $record_ID = $s[1];
                }
              }
              $this->items[] = $record_ID;

              /*
            if($this->listType == 0) {
              $this->items[] = $record_ID;
              $this->items[] = array(
                'record_ID' => $record_ID,
                'content' => $item['content'],
                'type' => $item['type'],
                'url' => $item['url']
              );
              } // listType 0
              elseif($this->listType == 1) {
              } // listType 1
              */

            }
          }
          else {
            foreach($arr['items'] as $item) {
              $this->items[] = $item;
            }
            /* ogmt/v1.0/adminogmt/objectgroup.htm
             * "items": [
                  "npg_S_NPG.85.20"
                ]
             */
          }

          if(array_key_exists('listType', $arr)) {
            $this->listType = $arr['listType'];
          }
          elseif($b_content_items && $this->size > 0) {
            $this->listType = 0;
          }
          elseif(!$b_content_items) {
            // if listType isn't set, this might be a saved search or it might be an empty object listing
            if($this->size > 0) {
              $this->listType = 1;
            }
          }

          if($this->listType == 1 && $this->size > 0) {
            foreach($this->items as $k => $v) {
              if(strpos($v, ':') !== false ) {
                $this->queryFacets[] = $v;
              }
              else {
                $this->queryTerms = urldecode($v);
              }
            }
            $this->items = array();
      }

        } // if we have some items

      if(array_key_exists('settings', $arr)) {
        $this->settings = $arr['settings'];
      }

    } // load the object listing from an array

    public function saveSearch($query = NULL, $fquery = NULL) {
    // save the existing list by executing a clear(),
    // then possibly a modifyType,
    // then an addItems() using $this->items, $this->listType

      $local_query_terms = $this->queryTerms;
      $local_query_facets = $this->queryFacets;

      if(NULL !== $query) {
        $local_query_terms = $query;
      }
      if(NULL !== $fquery) {
        $local_query_facets = $fquery;
      }

      if(NULL == $local_query_terms) {
        $this->errors[] = 'No query terms have been set for this object listing. Cannot save search. saveSearch()';
        return false;
      }

        $ok = $this->clear();
        if(!$ok) {
        //  return false;
        }

      if($this->listType == 0) {
        // if we are saving search params but listType is set for hand-picked objects, change the listType
          $ok = $this->modifyType(1);
          $this->listType = 1;
          if(!$ok) {
          //  return false;
          }
      }

      $this->queryTerms = $local_query_terms;
      $this->queryFacets = $local_query_facets;

      $ok = $this->addItems();
      return $ok;

    }

    public function save() {
      // save the existing list by executing a clear(),
      // then possibly a modifyType,
      // then an addItems() using $this->items, $this->listType

      // action=clear will return 404 if the list doesn't exist yet
      // so we can't test for a return value
      $this->clear(false);

      if($this->listType == 1) {
        // if we are saving hand-picked list of items but listType is currently set for search params, change the listType
        // action=modifyType will return 404 if the list doesn't exist yet
        // so we can't test for a return value
        $this->modifyType(0);
		    $this->listType = 0;
      }

      $this->errors = array(); // @todo- only ignore 404
      $ok = $this->addItems();
      return $ok;

    } // save the object listing

    public function modifyType($newType) {

      if(NULL == $this->objectGroupId) {
        $this->errors[] = 'Object Group Id not set. Could not load Object List. load()';
          return false;
      }

      if($newType !== 0 && $newType !== 1) {
        $this->errors[] = 'List type must be 0 or 1. modifyType()';
          return false;
      }

      $service = 'ogmt/v1.0/adminogmt/editObjectListing.htm';
      $params = array();
      $params['action'] = 'modifyType'; //action  (add,remove,move,review,clear,modifyType); defaults to review
      $params['objectGroupId'] = $this->objectGroupId;
      if(isset($this->pageId)) {
        $params['pageId'] = $this->pageId;
      }
      $params['listType'] = $newType;

      $got_object = $this->edan_connection->callEDAN($service, $params);

      $this->results_json = $this->edan_connection->getResultsJSON();
      $this->results_info = $this->edan_connection->getResultsInfo();
      $this->results_raw = $this->edan_connection->getResultsRaw();

      if(!$got_object) {
        $this->errors += $this->edan_connection->getErrors();
        $this->errors[] = "Could not modify list type for object list. modifyType()";
        return false;
      }

      if(isset($this->results_json) && array_key_exists('error', $this->results_json )) {
        $this->errors[] = $this->results_json['error'];
          return false;
      }

        $this->listType = $newType;
      return true;

    }

  } // ObjectList

} // namespace EDAN\OGMT

?>