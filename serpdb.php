<?php

/*

Plugin Name: Serp Dashboard
Plugin URI: http://www.juust.org/index.php/serp/serp-dashboard-wordpress-plugin/
Description: Seo serp scraper.
Version: 1.0.3  
Author: Juust
Author URI: http://www.juust.org/

*/

/*  Copyright 2009  Juust  (admin@juust.org)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/




/* install and uninstall functions */

$serpdb_db_version = "1.0.3";
serpdb_db_update();

/**
 * Uninstall
 *
 * This method will remove database tables
 * and options, on deactivation and uninstall
 *
 * @param void
 * @return void
 */
function serpdb_uninstall()
{
    
    global $wpdb;
    
    $table_name = $wpdb->prefix . "serpdb_terms";
    $wpdb->query( "DROP TABLE IF EXISTS " . $table_name);    

    $table_name = $wpdb->prefix . "serpdb_results";
    $wpdb->query( "DROP TABLE IF EXISTS " . $table_name);    

    $table_name = $wpdb->prefix . "serpdb_results_mine";
    $wpdb->query( "DROP TABLE IF EXISTS " . $table_name);    

    
  //delete all options
    delete_option('serpdb_db_version');
    delete_option("serpdb_domain");
    delete_option("serpdb_searchdepth");
    delete_option("serpdb_permutations");
    delete_option("serpdb_keepall");
    delete_option("serpdb_period");
    delete_option("serpdb_engine_language");
}


/**
 * Install
 *
 * This method will install database tables,
 * add standard options and attempt to create
 * search terms from tags
 *
 * @param void
 * @return void
 */
function serpdb_install () {
  
  //options :
  
    add_option("serpdb_db_version", "0.0.0");
    add_option("serpdb_domain", parse_url(get_option('home'), PHP_URL_HOST));
    add_option("serpdb_searchdepth", "100");
    add_option("serpdb_permutations", "3");
    add_option("serpdb_keepall", "0");
    add_option("serpdb_period", "1");
    add_option("serpdb_engine_language", "en");

    serpdb_db_update();

    //runonce
    serpdb_tags_as_terms();

}

/**
 * Update
 *
 * This method will update database tables
 * and add or delete options
 * if the database version differs
 *
 * @param void
 * @return void
 */

function serpdb_db_update()
{
   global $serpdb_db_version;

   if(get_option( "serpdb_db_version" )==$serpdb_db_version) return;

   global $wpdb;

   $table_name = $wpdb->prefix . "serpdb_terms";
   $sql = " CREATE TABLE `$table_name` (
            `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `term` TEXT NOT NULL ,
            `freq` TEXT NOT NULL ,
            `nextdate` TEXT NOT NULL,
            `source` TEXT NOT NULL ,
            `engine_language` TEXT NOT NULL ,
            `depth` TEXT NOT NULL ,
            `char` TEXT NOT NULL
            ) ENGINE = MYISAM";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $table_name = $wpdb->prefix . "serpdb_results";
     $sql = "CREATE TABLE `$table_name` (
            `id` INT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
            `enginecode` TEXT NOT NULL ,
            `keywords` TEXT NOT NULL ,
            `date` TEXT NOT NULL ,
            `position` TEXT NOT NULL ,
            `url` TEXT NOT NULL ,
            `host` TEXT NOT NULL ,
            `source` TEXT NOT NULL
            ) ENGINE = MYISAM";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    $table_name = $wpdb->prefix . "serpdb_results_mine";
    $sql = "CREATE TABLE `$table_name` (
            `id` INT NOT NULL  AUTO_INCREMENT PRIMARY KEY,
            `enginecode` TEXT NOT NULL ,
            `keywords` TEXT NOT NULL ,
            `date` TEXT NOT NULL ,
            `position` TEXT NOT NULL ,
            `url` TEXT NOT NULL ,
            `host` TEXT NOT NULL ,
            `source` TEXT NOT NULL
            ) ENGINE = MYISAM";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    update_option( "serpdb_db_version", $serpdb_db_version );

}


/*
   hooks, 
   backend admin page, css and js files
*/

register_activation_hook(__FILE__,'serpdb_install');

register_deactivation_hook(__FILE__, 'serpdb_uninstall');
register_uninstall_hook(__FILE__, 'serpdb_uninstall');

add_action('admin_menu', 'serpdb_plugin_menu');

function serpdb_plugin_menu()
{
  $page = add_options_page(__('Serp Dashboard Options'), __('Serp Dashboard'), 8, __FILE__, 'serpdb_gui');
  add_action("admin_print_scripts-$page", 'serpdb_list_scripts');
  add_action("admin_print_styles-$page", 'serpdb_list_styles');
}

// ====== insert jquery and css 
function serpdb_list_scripts() {
  wp_enqueue_script( "serpdb-box", path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) )."/serpdb.menu.js"), array( 'jquery' ) );
}

function serpdb_list_styles() {
  wp_enqueue_style('serpdb-tables', path_join( WP_PLUGIN_URL, basename( dirname( __FILE__ ) ).'/tables.css'), false, "1.0", "all" ); 
}



/**
 * Turns tags into search-phrases.
 *
 * This method will use the most popular tags, 
 * create all permutations, and store them as
 * searchphrases
 *
 * @param bool $delete     obsolete
 * @return void
 */

function serpdb_tags_as_terms($delete = false) {
    
    $tags = serpdb_permute_blog_tags(get_option("serpdb_permutations"));
    if($tags) {
        foreach($tags as $t)
        {
           if(!serpdb_term_exists($t)) serpdb_term_add_std($t);
        }        
    }
}

/**
 * permutes blog tags
 *
 * This method takes the top n blog tags,
 * makes every combination and returns as
 * terms (search phrases). Uses two helper
 * functions
 *
 * @param int $number 
 * @return array $terms
 */    
function serpdb_permute_blog_tags($number)
{
    $tag_array = get_tags('orderby=count&order=DESC&number='.$number);
    if(!$tag_array) return;
    for ($x = 0; $x < count($tag_array); $x++) $tags[] = $tag_array[$x]->name;
    $terms = serpdb_make_list($tags, $number);
    return $terms;
}

                function serpdb_make_list($array, $length) {
                  return serpdb_make_list_rec('', $array, $length);
                }
                
                function serpdb_make_list_rec($prefix, $array, $length) {
                  $ret = array();
                  $append = array();
                
                  foreach ($array as $a) {
                    if(strpos('p'.$prefix, $a) <1) {
                        if(!$prefix) {
                            $ret[] = trim($prefix. ' '. $a). "\n";
                        } else {
                            if(strpos('p'.$a, $prefix) <1) {
                                    $ret[] = trim($prefix. ' '. $a). "\n";
                            }
                        }
                    }
                    if ($length === 1) continue;
                
                   if(strpos('p'.$prefix, $a) <1) {
                        if(!$prefix) {
                           $new_length = $length - 1;
                           $new_prefix = $prefix . ' '. $a;
                           $append = array_merge($append, serpdb_make_list_rec($new_prefix, $array, $new_length));
                        } else {
                            if(strpos('p'.$a, $prefix) <1) {
                 
                               $new_length = $length - 1;
                               $new_prefix = $prefix . ' '. $a;
                               $append = array_merge($append, serpdb_make_list_rec($new_prefix, $array, $new_length));
                            }
                        }
                   }
                  }
                  return array_merge($ret, $append);
                }



/**
 * Checks if a term exists in the database.
 *
 * This method will use the most popular tags, 
 * create all permutations, and store them as
 * searchphrases
 *
 * @param string $term     
 * @return boolean
 */
function serpdb_term_exists($term)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "serpdb_terms"; 
    $sql = "SELECT * from `$table_name` WHERE `term`='".$term."'";
    $results = $wpdb->get_results( $sql);
    if($results) return true;
    return false;
}


/**
 * runs pending jobs
 *
 * This method is hooked into the shutdown event,
 * the end of the page generation and picks any
 * pending job (search, stats, p2p)
 *
 * @param void  
 * @return boolean
 */
function serpdb_runaserp($thisterm='') {

    //retrieve terms that are scheduled to be queried
    if($thisterm=='') {
        $terms = serpdb_get_todays_terms();    
    } else {
        $allterms = serpdb_get_terms();
        foreach($allterms as $term) {
            if($term->term==$thisterm) {
                $terms[] = $term;
                
            }
        }
        
    }
    
    
    if(!$terms) return;
    
    foreach($terms as $t)
    {
        //get characteristic to watch
        $watch = $t->char;
        $lang =  $t->engine_language;
        $depth = $t->depth;

        if($watch=='') $watch = get_option('serpdb_domain');
        if($lang =='') $lang = get_option('serpdb_engine_language');
        if($depth < 100 || $depth > 1000 || $depth == '' ) $depth = get_option('serpdb_searchdepth');

        //retrieve results
        $return = serp_google($t->term, $watch, $depth, $lang);

        //store results 
        serpdb_store_results($return[1],$t->term, true);
        serpdb_store_results($return[2],$t->term, false);
        
        //schedule term for next retrieval
        serpdb_update_term($t->term);
              
        return;
    }
}
add_action( 'shutdown', 'serpdb_runaserp' );


/**
 * retrieve pending searches
 *
 * This method retrieves searches where nextdate is (before) today
 *
 * @param void  
 * @return array $results
 */
function serpdb_get_todays_terms()
{
   global $wpdb;
   $table_name = $wpdb->prefix . "serpdb_terms"; 
   $sql = "SELECT * from `$table_name` WHERE `nextdate`<='".date("Ymd")."'";
   $results = $wpdb->get_results( $sql);
   return $results;
}


/**
 * gets terms
 *
 * This method retrieves all terms from database
 *
 * @param void  
 * @return array $results
 */
function serpdb_get_terms()
{
   global $wpdb;
   $table_name = $wpdb->prefix . "serpdb_terms"; 
   $sql = "SELECT * from `$table_name`";
   $results = $wpdb->get_results( $sql);
   return $results;
}


/**
 * get last result list
 *
 * This method retrieves the stored results for
 * the user from database
 *
 * @param void
 * @return array $results
 */
function serpdb_my_last_results()
{
    global $wpdb;
    $dbname = $wpdb->prefix ."serpdb_results_mine";
    $results = $wpdb->get_results("SELECT * FROM `".$dbname."` ORDER BY position, keywords ASC");
    return $results;
}


/**
 * get google result list
 *
 * This method retrieves a Google result list based
 * on custom parameters and parses out the urls
 *
 * @param string $varkeywords, string $varhost, int $vardepth, string $varlanguage
 * @return mixed array 
 *          int $hasresult
 *          array $myresults
 *          array $results
 *          $q 
 */
function serp_google($varkeywords, $varhost, $vardepth=100, $varlanguage="en")
{

    $start = 1;
    $numberofresults = 100;

    $max=round($vardepth/$numberofresults); 
    if($max<1) $max=1;
    if($max>10) $max=10;

    $i=0;

    for($q=0; $q<$max; $q++) {
        
        //wait half a second between queries
        if($q>0) usleep(500000);

        $jj=$q*$numberofresults;//+1;
        $kk=$jj+$numberofresults;

        $vargoogleresultpage = "http://www.google.com/search?as_q=".urlencode(trim($varkeywords))."&num=".$numberofresults."&start=".$jj."&hl=".$varlanguage;
        flush();
        
      
        $googleresponse = serpdb_getPageData($vargoogleresultpage);
        if(!$googleresponse) { /* no page, google went down */ }
        
        //break up at "class" marker
        $googlehits = preg_split('/class=r><a /', $googleresponse, -1, PREG_SPLIT_OFFSET_CAPTURE);
        
        foreach($googlehits as $googlehit){
            $i++;
        
            preg_match("/href=\"(.*?)\"/", $googlehit[0], $t, PREG_OFFSET_CAPTURE);
            if($i > 1){
                $serp = $i -1;
           
                $results[] = array(
               
                                   "enginecode"=>"google_".$varlanguage,
                                   "keywords"=>trim($varkeywords),
                                   "date"=>date("Ymd"),
                                   "position"=>$serp,
                                   "host"=>@parse_url($t[1][0], PHP_URL_HOST),
                                   "url"=>$t[1][0],
                                   "source"=>"me");

                //check if varhost is in the url
                
                if(@parse_url($t[1][0], PHP_URL_HOST)==$varhost) {                    
                        $hasresult++;
                        $myresults[] = array(
               
                                   "enginecode"=>"google_".$varlanguage,
                                   "keywords"=>trim($varkeywords),
                                   "date"=>date("Ymd"),
                                   "position"=>$serp,
                                   "host"=>@parse_url($t[1][0], PHP_URL_HOST),
                                   "url"=>$t[1][0],
                                   "source"=>"me");
                }        
            }
        }
    }
        
    $hasresult = array("resultaten"=>$hasresult);
    return array($hasresult, $myresults, $results, $q);    
}


/**
 * store result list
 *
 * This method stores results in the database
 *
 * @param array $results, string $term, bool $mine
 * @return void
 */
function serpdb_store_results($array_results, $term, $mine=false)
{
        if($mine == false) {
            if(get_option('serpdb_keepall')==0) return;
        }
    
        if(!$array_results) return;
        if(empty($array_results)) return;

        global $wpdb;
        
        $dbname = $wpdb->prefix ."serpdb_results";
        
        if($mine) $dbname .= "_mine";

        $mysql = "DELETE FROM `".$dbname."` WHERE `keywords`='".trim($term)."'";
        $wpdb->query($mysql);
    
        foreach($array_results as $res)
        {
            $mysql =
            "INSERT INTO `".$dbname."` 
            (
             `enginecode`,`keywords`,`date`,`position`,`url`,`host`,`source`   
            ) VALUES (
            '".$res['enginecode']."',
            '".trim($res['keywords'])."',
            '".$res['date']."',
            '".$res['position']."',
            '".$res['url']."',
            '".$res['host']."',     
            '".$res['source']."'
            )";

            $wpdb->query($wpdb->prepare($mysql));
        }
}


/**
 * store result list
 *
 * This method stores results in the database
 *
 * @param array $results, string $term, bool $mine
 * @return void
 */
function serpdb_term_add_std($term)
{
    global $wpdb;
    $dbname = $wpdb->prefix . "serpdb_terms"; 

    $sql = "INSERT INTO `$dbname` (
                `term` ,
                `freq` ,
                `depth`,
                `nextdate`,  
                `source`,
                `char`,
                `engine_language`
            ) VALUES (
                '".$term."',
                '".get_option('serpdb_period')."',
                '".get_option('serpdb_searchdepth')."',
                '".date("Ymd")."',
                'tags',
                '".get_option('serpdb_domain')."',
                '".get_option('serpdb_engine_language')."'
            )";   
     $wpdb->query($wpdb->prepare($mysql));
    
}


/**
 * store result list
 *
 * This method stores results in the database
 *
 * @param array $results, string $term, bool $mine
 * @return void
 */
function serpdb_get_tag_results($term)
{
        global $wpdb;
        
        $dbname = $wpdb->prefix .strtolower("serpdb_results");
        $sql = "SELECT * from `$dbname` WHERE `keywords`='".trim($term)."' ORDER by `date` DESC";
        $data = $wpdb->get_results( $sql);
        
        if(!$data)
        {
            $dbname = $wpdb->prefix . "serpdb_terms"; 
            $sql = "SELECT * from `$dbname` WHERE `term`='$term'";
            $termdata = $wpdb->get_results( $sql);
            
            if(!$termdata) {
                //add the term to the term set
                $sql = "INSERT INTO `$dbname` (
                            `term` ,
                            `freq` ,
                            `depth`,
                            `nextdate`,  
                            `source`,
                            `engine_language`,
                            `char`
                        ) VALUES (
                            '".$term."',
                            '".get_option('serpdb_period')."',
                            '".get_option('serpdb_searchdepth')."',
                            '".date("Ymd")."',
                            'tags',
                            '".get_option('serpdb_engine_language')."',
                            '".get_option('serpdb_domain')."'
                        )";
                        
                $wpdb->query($wpdb->prepare($mysql));
            }
            
            $dbname = $wpdb->prefix . "serpdb_terms"; 
            $sql = "SELECT * from `$dbname` WHERE `term`='$term'";
            $termdata = $wpdb->get_results( $sql);
            
            foreach($termdata as $t)
            {
                $watch = $t->char;
                $lang = $t->engine_language;
                $depth = $t->depth;
                break;
            }            

            //query the term
            //update the term
            
            if($watch=='') $watch = get_option('serpdb_domain');
            if($lang =='') $lang = get_option('serpdb_engine_language');
            if($depth < 100 || $depth > 1000 || $depth == '' ) $depth = get_option('serpdb_searchdepth');
            
            $serpdata = serp_google($term, $watch, $depth, $lang);

            if($serpdata[2]) serpdb_store_results($serpdata[2], $term, false);

            if($serpdata[1]) serpdb_store_results($serpdata[1], $term, true);

            $nextdate = serpdb_update_term($term); 
               
            $dbname = $wpdb->prefix ."serpdb_results";
            $sql = "SELECT * from `$dbname` WHERE `keywords`='".$term."' ORDER by `date` DESC";
            $data = $wpdb->get_results( $sql);

        }
        
        return $data;        
}

/**
 * update term date 
 *
 * sets terms next date after search
 *
 * @param string $term
 * @return string $n
 */

function serpdb_update_term($term)
{
    global $wpdb;
    $table_name = $wpdb->prefix . "serpdb_terms"; 
    $sql = "SELECT * from `$table_name` WHERE `term`='".$term."'";
    $results = $wpdb->get_results( $sql);
    
    foreach($results as $r) {
        $n = date("Ymd", strtotime("+".$r->freq." days"));
        $sql = "UPDATE `$table_name` SET `nextdate`='".$n."' WHERE `term`='".$term."'";
        $wpdb->query($wpdb->prepare($sql));
    }
    return $n;
}


/*
  ajax post request handler
*/

add_action('wp_ajax_serpdb_save_settings', 'serpdb_save_settings', 10);

/**
 * general ajax handler
 *
 * This method processes all jquery requests
 * returns a success flag and where appropriate
 * html and data
 *
 * @param void
 * @return string $message_result
 */

function serpdb_save_settings()
{
     //must be a post request and originate from same host
    if(!$_SERVER['REQUEST_METHOD']=="POST") die('no GET type requests');
    if(!$_SERVER['HTTP_HOST']==parse_url(WP_PLUGIN_URL, PHP_URL_HOST)) die('request originates from foreign server'); 

    //get subject (~~function)
    $subject = $_POST['subject'];

    //empty subject : return FALSE
    if ($subject == "") {
            $message_result = array(
                    'message' => 'has no subject',
                    'success' => FALSE
                    );
    }

    /*
        plugin settings :
            serpdb_domain
            serpdb_searchdepth
            serpdb_permutations
            serpdb_period
            serpdb_buildarchive
            serpdb_keepall
            
    */


    if ($subject == "serpdb_domain") {
            update_option('serpdb_domain', $_POST['domain']);
            $message_result = array(
                    'message' => $_POST['domain'].' domain set',
                    'success' => TRUE
                    );
    }


    if ($subject == "serpdb_searchdepth") {
            update_option('serpdb_searchdepth', $_POST['searchdepth']);
            $message_result = array(
                    'message' => ' depth set to '.$_POST['searchdepth'],
                    'success' => TRUE
                    );
    }


    if ($subject == "serpdb_permutations") {
            update_option('serpdb_permutations', $_POST['permutations']);
            $message_result = array(
                    'message' => ' permutations set to '.$_POST['permutations'],
                    'success' => TRUE
                    );
    }

    
    if ($subject == "serpdb_period") {
            update_option('serpdb_period', $_POST['period']);
            $message_result = array(
                    'message' => $_POST['period'].' period set',
                    'success' => TRUE
                    );
    }    


    
    if ($subject == "serpdb_keepall") {
            update_option('serpdb_keepall', $_POST['keepall']);
            
            //if set to 0 : clear erpdb_results
            $themessage = "keep all results";
            if($_POST['keepall']==0)
            {
                global $wpdb;
                $tname = $wpdb->prefix. "serpdb_results";
                $wpdb->query("DELETE FROM $tname");
                $themessage = "keeping only own results, other results deleted";
            }
            
            $message_result = array(
                    'message' => $themessage,
                    'success' => TRUE
                    );
    }


    if ($subject == "serpdb_engine_language") {
            update_option('serpdb_engine_language', $_POST['engine_language']);
            $message_result = array(
                    'message' => 'engine language set to : '.$_POST['engine_language'],
                    'success' => TRUE
                    );
    }



/* terms functions */

    //returns the terms defaults

    if ($subject == "standard_serpdb_term") {
           $message_result = array(
                    'message' => 'standards set',
                    'characteristic'=> get_option("serpdb_domain"),
                    'depth'=> get_option("serpdb_searchdepth"),
                    'period'=> get_option("serpdb_period"),
                    'engine_language' => get_option("serpdb_engine_language"),
                    'success' => TRUE
                    );
    }       

    //deletes the term

    if ($subject == "delete_serpdb_term") {

            $term_recordid = $_POST['term_recordid'];
            $term_term = trim($_POST['term_term']);

            global $wpdb;
            $dbname = $wpdb->prefix . "serpdb_terms";

            //remove from terms by recordid
            $sql = "DELETE FROM $dbname WHERE `id` = ".$term_recordid;
            $wpdb->query($wpdb->prepare($sql));
            
            //remove from results by keyword
            $dbname = $wpdb->prefix . "serpdb_results";
            $sql = "DELETE FROM $dbname WHERE `keywords` = '".$term_term."'";
            $wpdb->query($wpdb->prepare($sql));
            
            $dbname = $wpdb->prefix . "serpdb_results_mine";
            $sql = "DELETE FROM $dbname WHERE `keywords` = '".$term_term."'";
            $wpdb->query($wpdb->prepare($sql));

            $message_result = array(
                    'message' => $_POST['term_term'].' is deleted',
                    'success' => TRUE
                    );           
    }

    //updates or adds a term 

    if ($subject == "update_serpdb_term") {

            //if no term : exit
            $term_term = $_POST['term_term'];
            if($term_term == '')
            {
                $message_result = array(
                    'message' => 'term missing',
                    'success' => FALSE
                    );
                echo json_encode($message_result);
                exit;
            }

            global $wpdb;
            $dbname = $wpdb->prefix . "serpdb_terms";
            
            $term_recordid = $_POST['term_recordid'];
            $term_freq = $_POST['term_freq'];
            $term_char = $_POST['term_char'];
            $term_engine_language = $_POST['term_engine_language'];
            $term_depth = $_POST['term_depth'];
            $term_nextdate = $_POST['term_nextdate'];

            //take default options if necessary            
            if($term_depth == '' || $term_depth < 100) $term_depth = 100;
            if($term_freq=='' || $term_freq ==0) $term_freq = 7;
            if($term_freq>31) $term_freq = 30;
            
            if($term_char =='' ) $term_char = get_option('serpdb_domain');
 
            if($term_engine_language == '') $term_engine_language = get_option('serpdb_engine_language');
            
            if($term_nextdate == '') $term_nextdate = date("Ymd");
            
            //if no id, it's new, otherwise, its an update          
            if(!$term_recordid || $term_recordid=='' || $term_recordid==0)
            {
               $sql = "INSERT INTO $dbname 
                    (`freq`, `term`, `char`, `depth`, `engine_language`, `nextdate`)
                    VALUES (
                        '".$term_freq."',
                        '".$term_term."',
                        '".$term_char."',
                        '".$term_depth."',
                        '".$term_engine_language."',
                        '".$term_nextdate."'
                    )";
                     
            } else {
 
               $sql = "UPDATE $dbname 
                       SET
                        `freq` = '".$term_freq."' ,
                        `term` = '".$term_term."' ,
                        `char` = '".$term_char."' ,
                        `depth` = '".$term_depth."',
                        `engine_language` = '".$term_engine_language."',
                        `nextdate` = '".$term_nextdate."'
                       WHERE `id` = ".$term_recordid;
            }
            
            $wpdb->query($wpdb->prepare($sql));
            
            if(!$term_recordid || $term_recordid=='' || $term_recordid==0)
            {
                //new
                serpdb_runaserp($term_term);
            }
            
            $message_result = array(
                    'message' => $_POST['term_term'].' term updated',
                    'id'        => $term_recordid,
                    'freq'      => $term_freq,
                    'term'      => $term_term,
                    'tchar'     => $term_char,
                    'engine_language'=> $term_engine_language,
                    'depth'     => $term_depth,
                    'nextdate'  => $term_nextdate,
                    'success' => TRUE
                    );
    }

 
/*  run a serp */

  if ($subject == "run_serpdb_term") {

           global $wpdb;
           $dbname = $wpdb->prefix . "serpdb_terms";
            
           $id = $_POST['term_recordid'];
           
           $sql="SELECT * FROM $dbname WHERE `id`=".$id;
           $res= $wpdb->get_results($sql);
           foreach($res as $t)
           {
                $watch = $t->char;
                $lang = $t->engine_language;
                $depth = $t->depth;
  
                if($watch=='') $watch = get_option('serpdb_domain');
                if($lang =='') $lang = get_option('serpdb_engine_language');
                if($depth < 100 || $depth > 1000 || $depth == '' ) $depth = get_option('serpdb_searchdepth');
                
                $return = serp_google($t->term, $watch, $depth, $lang);
                if($return[1]) {
                    $rescount += count($return[1]);
                    serpdb_store_results($return[1], $t->term, true);
                }
                if($return[2]) serpdb_store_results($return[2], $t->term, false);
                $nextdate = serpdb_update_term($t->term);          
           }         

           $message_result = array(
                    'message' => 'serp updated ('.$rescount.' results)',
                    'nextdate' => $nextdate,
                    'success' => TRUE
                    );
            
            echo json_encode($message_result);
            exit;
 
    }
   

/*  refreshing the tables */

    if ($subject == "refresh_serpdb_term") {
            $message_result = array(
                    'message' => '',
                    'thehtml'=> serpdb_termsTable(),
                    'success' => TRUE
                    );
    }       

    if ($subject == "refresh_serpdb_result") {
            $message_result = array(
                    'message' => '',
                    'thehtml'=> serpdb_resultsTable(),
                    'success' => TRUE
                    );
    }       

/*  encode json message and exit */

    echo json_encode($message_result);
    exit;
}

/**
 * terms html table 
 *
 * This method outputs terms html table for backend gui
 * 
 * @param void
 * @return string output
 */
function serpdb_termsTable()
{
    $myactiveterms = serpdb_get_terms();
    if($myactiveterms) {

        $output .= '<table>';

        $output .= '<tr class="even">';
            $output .= '<td>term</td>';
            $output .= '<td>depth</td>';
            $output .= '<td>char</td>';
            $output .= '<td>freq</td>';
            $output .= '<td>next</td>';
            $output .= '<td>lang</td>'; 
            $output .= '<td>cfg, run, del</td>';
        $output .= '</tr> ';

      
        $n=1;
        foreach($myactiveterms  as $t) {
            $n++;
            $the_class = "odd"; 
            if(round($n/2)==$n/2) $the_class = "even";
            
            
            $output .= '<tr class="'.$the_class.'">';
               $output .= '<td id="tterm'.$t->id.'">'.$t->term.'</td>';
               $output .= '<td id="tdepth'.$t->id.'">'.$t->depth.'</td>';
               $output .= '<td id="tchar'.$t->id.'">'.$t->char.'</td>';
               $output .= '<td id="tfreq'.$t->id.'">'.$t->freq.'</td>';
               $output .= '<td id="tnextdate'.$t->id.'">'.$t->nextdate.'</td>';
               $output .= '<td id="tengine_language'.$t->id.'">'.$t->engine_language.'</td>';
               
               $output .= '<td>';
                   $output .= '<img class="imgterm cfg" id="cfg'.$t->id.'" src="'.path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ).'/32px-Crystal_Clear_action_configure.png" />';    
                   $output .= '<img class="imgterm run" id="cfg'.$t->id.'" src="'.path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ).'/32px-Crystal_Clear_action_forward.png" />';    
                   $output .= '<img class="imgterm del" id="cfg'.$t->id.'" src="'.path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ).'/32px-Crystal_Clear_action_button_cancel.png" />';    
               $output .= '</td>';
           $output .= '</tr> ';
        }
       $output .= '</table>';
    }
    return $output;
}


/**
 * results html table 
 *
 * This method outputs results html table for backend gui
 * 
 * @param void
 * @return string output
 */
function serpdb_resultsTable()
{
  //serpdb_runaserp();

  $mytags= serpdb_my_last_results();
  $output = '';
  if($mytags) {
    $output .= '<table>';
        $the_class = "even"; 
        $output .= '<tr class="'.$the_class.'">';
        $output .= '     <td>term</td>';
        $output .= '     <td>pos</td>';
        $output .= '     <td>url</td>';
        $output .= '     <td>date</td>';
        $output .= '     <td>engine</td>';
        $output .= '     <td></td>';
        $output .= '</tr>'; 

        $n=1;
        foreach($mytags  as $t) {
            $n++;
            $the_class = "odd"; 
            if(round($n/2)==$n/2) $the_class = "even";
            
           $output .= '<tr class="'.$the_class.'">';
           $output .= '     <td>'.$t->keywords.'</td>';
           $output .= '     <td>'.$t->position.'</td>';
           $output .= '     <td><a href="'.$t->url.'" rel="nofollow" target="_blank">'.$t->url.'</a></td>';
           $output .= '     <td>'.$t->date.'</td>';
           $output .= '     <td>'.$t->enginecode.'</td>';
           $output .= '     <td></td>';
           $output .= '</tr>'; 
        }
        $output .= '</table>';
     }
     return $output;
}


/**
 * forms
 *
 * This method outputs the html backend gui
 * as tabbed form
 *
 * @param void
 * @return 
 */

function serpdb_gui()
{
?>
    <div class="wrap" style="padding-top: 50px;">
    <div id="loadingDiv" class="loadDiv"><img src="<?php echo path_join(WP_PLUGIN_URL, basename( dirname( __FILE__ ) ) ); ?>/loader.gif" alt="." /></div>
    <div id="sys_message" class="updated fade"><p><strong>.</strong></p></div>
        
           
    <div id="gwt_box_1" class="gwt_box">

        <div class="gwt_area">  
             
            <ul class="tabs">  
                <li><a class="tab" title="serpdb_Results" href="#" id="tab_1" class="active">results</a></li>  
                <li><a class="tab" title="serpdb_Admin" href="#" id="tab_2">admin</a></li>  
                <li><a class="tab" title="serpdb_Searches" href="#" id="tab_3">searches</a></li>
                <li><a class="tab" title="serpdb_EditSearches" href="#" id="tab_4">edit searches</a></li>
            </ul>  
             

            <div id="serpdb_Results" class="content">
                <?php echo serpdb_resultsTable(); ?>
            </div>

            <div id="serpdb_Admin" class="content">
                <form action="" method="post">
                    <table>
                         
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_myhost" align="left">host</label></th>
                         <td id="serpdb_myhost"><?php echo get_option('home'); ?></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>
                       
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_domain" align="left">characteristic</label></th>
                         <td><input name="serpdb_domain" type="text" id="serpdb_domain" value="<?php echo get_option('serpdb_domain'); ?>" /></td>
                         <td></td>
                         <td><a class="gwt_anchor" id="serpdb_domain_update" href="#">update</a></td>
                         <td></td>
                       </tr>

                       <tr valign="top">
                         <th scope="row"><label for="serpdb_period" align="left">period</label></th>
                         <td><input name="serpdb_period" type="text" id="serpdb_period" value="<?php echo get_option('serpdb_period'); ?>" /></td>
                         <td></td>
                         <td><a class="gwt_anchor" id="update_serpdb_period" href="#">update</a></td>
                         <td></td>
                       </tr>

                       
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_searchdepth" align="left">search depth</label></th>
                         <td>
                             <?php $val = get_option('serpdb_searchdepth'); ?>
                             <select name="serpdb_searchdepth" id="serpdb_searchdepth">
                                 <option value="100" <?php echo  ($val == 100 ? 'selected' : ''); ?>>100 (  1 page )</option>
                                 <option value="200" <?php echo  ($val == 200 ? 'selected' : ''); ?>>200 (  2 pages)</option>
                                 <option value="300" <?php echo  ($val == 300 ? 'selected' : ''); ?>>300 (  3 pages)</option>
                                 <option value="400" <?php echo  ($val == 400 ? 'selected' : ''); ?>>400 (  4 pages)</option>
                                 <option value="500" <?php echo  ($val == 500 ? 'selected' : ''); ?>>500 (  5 pages)</option>
                                 <option value="600" <?php echo  ($val == 600 ? 'selected' : ''); ?>>600 (  6 pages)</option>
                                 <option value="700" <?php echo  ($val == 700 ? 'selected' : ''); ?>>700 (  7 pages)</option>
                                 <option value="800" <?php echo  ($val == 800 ? 'selected' : ''); ?>>800 (  8 pages)</option>
                                 <option value="900" <?php echo  ($val == 900 ? 'selected' : ''); ?>>900 (  9 pages)</option>
                                 <option value="1000" <?php echo  ($val == 1000 ? 'selected' : ''); ?>>1000 (  10 pages)</option>
                             </select>  
                             </td>
                         <td></td>
                         <td><a class="gwt_anchor" id="update_serpdb_searchdepth" href="#">update</a></td>
                         <td></td>
                       </tr>                  
      
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_keepall" align="left">keep all results</label></th>
                         <td>
                             <?php $val = get_option('serpdb_keepall'); ?>
                             <select name="serpdb_keepall" id="serpdb_keepall">
                                 <option value="0" <?php echo  ($val == 0 ? 'selected' : ''); ?>>only my own results</option>
                                 <option value="1" <?php echo  ($val == 1 ? 'selected' : ''); ?>>all results</option>
                             </select>                                
                             </td>
                         <td></td>
                         <td><a class="gwt_anchor" id="update_serpdb_keepall" href="#">update</a></td>
                         <td></td>
                       </tr>


                       <tr valign="top">
                         <th scope="row"><label for="serpdb_engine_language" align="left">engine (<?php echo get_option('serpdb_engine_language'); ?>)</label></th>
                         <td>
                             <?php $val = get_option('serpdb_engine_language'); ?>
                             <select name="serpdb_engine_language" id="serpdb_engine_language">
                                 <option value="af" <?php echo ($val == "af" ? 'selected' : ''); ?>>Afrikaans</option>
                                 <option value="sq" <?php echo ($val == "sq" ? 'selected' : ''); ?>>Albanian</option>
                                 <option value="am" <?php echo ($val == "am" ? 'selected' : ''); ?>>Amharic</option>
                                 <option value="ar" <?php echo ($val == "ar" ? 'selected' : ''); ?>>Arabic</option>
                                 <option value="hy" <?php echo ($val == "hy" ? 'selected' : ''); ?>>Armenian</option>
                                 <option value="az" <?php echo ($val == "az" ? 'selected' : ''); ?>>Azerbaijani</option>
                                 <option value="eu" <?php echo ($val == "eu" ? 'selected' : ''); ?>>Basque</option>
                                 <option value="be" <?php echo ($val == "be" ? 'selected' : ''); ?>>Belarusian</option>
                                 <option value="bn" <?php echo ($val == "bn" ? 'selected' : ''); ?>>Bengali</option>
                                 <option value="bh" <?php echo ($val == "bh" ? 'selected' : ''); ?>>Bihari</option>
                                 <option value="bs" <?php echo ($val == "bs" ? 'selected' : ''); ?>>Bosnian</option>
                                 <option value="br" <?php echo ($val == "br" ? 'selected' : ''); ?>>Breton</option>
                                 <option value="bg" <?php echo ($val == "bg" ? 'selected' : ''); ?>>Bulgarian</option>
                                 <option value="km" <?php echo ($val == "km" ? 'selected' : ''); ?>>Cambodian</option>
                                 <option value="ca" <?php echo ($val == "ca" ? 'selected' : ''); ?>>Catalan</option>
                                 <option value="zh-CN" <?php echo ($val == "zh-CN" ? 'selected' : ''); ?>>  Chinese (Simplified)</option>
                                 <option value="zh-TW" <?php echo ($val == "zh-TW" ? 'selected' : ''); ?>>  Chinese (Traditional)</option>
                                 <option value="co" <?php echo ($val == "co" ? 'selected' : ''); ?>>Corsican</option>
                                 <option value="hr" <?php echo ($val == "hr" ? 'selected' : ''); ?>>Croatian</option>
                                 <option value="cs" <?php echo ($val == "cs" ? 'selected' : ''); ?>>Czech</option>
                                 <option value="da" <?php echo ($val == "da" ? 'selected' : ''); ?>>Danish</option>
                                 <option value="nl" <?php echo ($val == "nl" ? 'selected' : ''); ?>>Dutch</option>
                                 <option value="en" <?php echo ($val == "en" ? 'selected' : ''); ?>>English</option>
                                 <option value="eo" <?php echo ($val == "eo" ? 'selected' : ''); ?>>Esperanto</option>
                                 <option value="et" <?php echo ($val == "et" ? 'selected' : ''); ?>>Estonian</option>
                                 <option value="fo" <?php echo ($val == "fo" ? 'selected' : ''); ?>>Faroese</option>
                                 <option value="tl" <?php echo ($val == "tl" ? 'selected' : ''); ?>>Filipino</option>
                                 <option value="fi" <?php echo ($val == "fi" ? 'selected' : ''); ?>>Finnish</option>
                                 <option value="fr" <?php echo ($val == "fr" ? 'selected' : ''); ?>>French</option>
                                 <option value="fy" <?php echo ($val == "fy" ? 'selected' : ''); ?>>Frisian</option>
                                 <option value="gl" <?php echo ($val == "gl" ? 'selected' : ''); ?>>Galician</option>
                                 <option value="ka" <?php echo  ($val == "ka" ? 'selected' : ''); ?>>Georgian</option>
                                 <option value="de" <?php echo  ($val == "de" ? 'selected' : ''); ?>>German</option>
                                 <option value="el" <?php echo  ($val == "el" ? 'selected' : ''); ?>>Greek</option>
                                 <option value="gn" <?php echo  ($val == "gn" ? 'selected' : ''); ?>>Guarani</option>
                                 <option value="gu" <?php echo  ($val == "gu" ? 'selected' : ''); ?>>Gujarati</option>
                                 <option value="ha" <?php echo  ($val == "ha" ? 'selected' : ''); ?>>Hausa</option>
                                 <option value="iw" <?php echo  ($val == "iw" ? 'selected' : ''); ?>>Hebrew</option>
                                 <option value="hi" <?php echo  ($val == "hi" ? 'selected' : ''); ?>>Hindi</option>
                                 <option value="hu" <?php echo  ($val == "hu" ? 'selected' : ''); ?>>Hungarian</option>
                                 <option value="is" <?php echo  ($val == "is" ? 'selected' : ''); ?>>Icelandic</option>
                                 <option value="id" <?php echo  ($val == "id" ? 'selected' : ''); ?>>Indonesian</option>
                                 <option value="ia" <?php echo  ($val == "ia" ? 'selected' : ''); ?>>Interlingua</option>
                                 <option value="ga" <?php echo  ($val == "ga" ? 'selected' : ''); ?>>Irish</option>
                                 <option value="it" <?php echo  ($val == "it" ? 'selected' : ''); ?>>Italian</option>
                                 <option value="ja" <?php echo  ($val == "ja" ? 'selected' : ''); ?>>Japanese</option>
                                 <option value="jw" <?php echo  ($val == "jw" ? 'selected' : ''); ?>>Javanese</option>
                                 <option value="kn" <?php echo  ($val == "kn" ? 'selected' : ''); ?>>Kannada</option>
                                 <option value="kk" <?php echo  ($val == "kk" ? 'selected' : ''); ?>>Kazakh</option>
                                 <option value="rw" <?php echo  ($val == "rw" ? 'selected' : ''); ?>>Kinyarwanda</option>
                                 <option value="rn" <?php echo  ($val == "rn" ? 'selected' : ''); ?>>Kirundi</option>
                                 <option value="ko" <?php echo  ($val == "ko" ? 'selected' : ''); ?>>Korean</option>
                                 <option value="ku" <?php echo  ($val == "ku" ? 'selected' : ''); ?>>Kurdish</option>
                                 <option value="ky" <?php echo  ($val == "ky" ? 'selected' : ''); ?>>Kyrgyz</option>
                                 <option value="lo" <?php echo  ($val == "lo" ? 'selected' : ''); ?>>Laothian</option>
                                 <option value="la" <?php echo  ($val == "la" ? 'selected' : ''); ?>>Latin</option>
                                 <option value="lv" <?php echo  ($val == "lv" ? 'selected' : ''); ?>>Latvian</option>
                                 <option value="ln" <?php echo  ($val == "ln" ? 'selected' : ''); ?>>Lingala</option>
                                 <option value="lt" <?php echo  ($val == "lt" ? 'selected' : ''); ?>>Lithuanian</option>
                                 <option value="mk" <?php echo  ($val == "mk" ? 'selected' : ''); ?>>Macedonian</option>
                                 <option value="mg" <?php echo  ($val == "mg" ? 'selected' : ''); ?>>Malagasy</option>
                                 <option value="ms" <?php echo  ($val == "ms" ? 'selected' : ''); ?>>Malay</option>
                                 <option value="ml" <?php echo  ($val == "ml" ? 'selected' : ''); ?>>Malayalam</option>
                                 <option value="mt" <?php echo  ($val == "mt" ? 'selected' : ''); ?>>Maltese</option>
                                 <option value="mi" <?php echo  ($val == "mi" ? 'selected' : ''); ?>>Maori</option>
                                 <option value="mr" <?php echo  ($val == "mr" ? 'selected' : ''); ?>>Marathi</option>
                                 <option value="mo" <?php echo  ($val == "mo" ? 'selected' : ''); ?>>Moldavian</option>
                                 <option value="mn" <?php echo  ($val == "mn" ? 'selected' : ''); ?>>Mongolian</option>
                                 <option value="sr-ME" <?php echo  ($val == "sr-ME" ? 'selected' : ''); ?>>  Montenegrin</option>
                                 <option value="ne" <?php echo  ($val == "ne" ? 'selected' : ''); ?>>Nepali</option>
                                 <option value="no" <?php echo  ($val == "no" ? 'selected' : ''); ?>>Norwegian</option>
                                 <option value="nn" <?php echo  ($val == "nn" ? 'selected' : ''); ?>>Norwegian (Nynorsk)</option>
                                 <option value="oc" <?php echo  ($val == "oc" ? 'selected' : ''); ?>>Occitan</option>
                                 <option value="or" <?php echo  ($val == "or" ? 'selected' : ''); ?>>Oriya</option>
                                 <option value="om" <?php echo  ($val == "om" ? 'selected' : ''); ?>>Oromo</option>
                                 <option value="ps" <?php echo  ($val == "ps" ? 'selected' : ''); ?>>Pashto</option>
                                 <option value="fa" <?php echo  ($val == "fa" ? 'selected' : ''); ?>>Persian</option>
                                 <option value="pl" <?php echo  ($val == "pl" ? 'selected' : ''); ?>>Polish</option>
                                 <option value="pt-BR" <?php echo  ($val == "pt-BR" ? 'selected' : ''); ?>>  Portuguese (Brazil)</option>
                                 <option value="pt-PT" <?php echo  ($val == "pt-PT" ? 'selected' : ''); ?>>  Portuguese (Portugal)</option>
                                 <option value="pa" <?php echo  ($val == "pa" ? 'selected' : ''); ?>>Punjabi</option>
                                 <option value="qu" <?php echo  ($val == "qu" ? 'selected' : ''); ?>>Quechua</option>
                                 <option value="ro" <?php echo  ($val == "ro" ? 'selected' : ''); ?>>Romanian</option>
                                 <option value="rm" <?php echo  ($val == "rm" ? 'selected' : ''); ?>>Romansh</option>
                                 <option value="ru" <?php echo  ($val == "ru" ? 'selected' : ''); ?>>Russian</option>
                                 <option value="gd" <?php echo  ($val == "gd" ? 'selected' : ''); ?>>Scots Gaelic</option>
                                 <option value="sr" <?php echo  ($val == "sr" ? 'selected' : ''); ?>>Serbian</option>
                                 <option value="sh" <?php echo  ($val == "sh" ? 'selected' : ''); ?>>Serbo-Croatian</option>
                                 <option value="st" <?php echo  ($val == "st" ? 'selected' : ''); ?>>Sesotho</option>
                                 <option value="sn" <?php echo  ($val == "sn" ? 'selected' : ''); ?>>Shona</option>
                                 <option value="sd" <?php echo  ($val == "sd" ? 'selected' : ''); ?>>Sindhi</option>
                                 <option value="si" <?php echo  ($val == "si" ? 'selected' : ''); ?>>Sinhalese</option>
                                 <option value="sk" <?php echo  ($val == "sk" ? 'selected' : ''); ?>>Slovak</option>
                                 <option value="sl" <?php echo  ($val == "sl" ? 'selected' : ''); ?>>Slovenian</option>
                                 <option value="so" <?php echo  ($val == "so" ? 'selected' : ''); ?>>Somali</option>
                                 <option value="es" <?php echo  ($val == "es" ? 'selected' : ''); ?>>Spanish</option>
                                 <option value="su" <?php echo  ($val == "su" ? 'selected' : ''); ?>>Sundanese</option>
                                 <option value="sw" <?php echo  ($val == "sw" ? 'selected' : ''); ?>>Swahili</option>
                                 <option value="sv" <?php echo  ($val == "sv" ? 'selected' : ''); ?>>Swedish</option>
                                 <option value="tg" <?php echo  ($val == "tg" ? 'selected' : ''); ?>>Tajik</option>
                                 <option value="ta" <?php echo  ($val == "ta" ? 'selected' : ''); ?>>Tamil</option>
                                 <option value="tt" <?php echo  ($val == "tt" ? 'selected' : ''); ?>>Tatar</option>
                                 <option value="te" <?php echo  ($val == "te" ? 'selected' : ''); ?>>Telugu</option>
                                 <option value="th" <?php echo  ($val == "th" ? 'selected' : ''); ?>>Thai</option>
                                 <option value="ti" <?php echo  ($val == "ti" ? 'selected' : ''); ?>>Tigrinya</option>
                                 <option value="to" <?php echo  ($val == "to" ? 'selected' : ''); ?>>Tonga</option>
                                 <option value="tr" <?php echo  ($val == "tr" ? 'selected' : ''); ?>>Turkish</option>
                                 <option value="tk" <?php echo  ($val == "tk" ? 'selected' : ''); ?>>Turkmen</option>
                                 <option value="tw" <?php echo  ($val == "tw" ? 'selected' : ''); ?>>Twi</option>
                                 <option value="ug" <?php echo  ($val == "ug" ? 'selected' : ''); ?>>Uighur</option>
                                 <option value="uk" <?php echo  ($val == "uk" ? 'selected' : ''); ?>>Ukrainian</option>
                                 <option value="ur" <?php echo  ($val == "ur" ? 'selected' : ''); ?>>Urdu</option>
                                 <option value="uz" <?php echo  ($val == "uz" ? 'selected' : ''); ?>>Uzbek</option>
                                 <option value="vi" <?php echo  ($val == "vi" ? 'selected' : ''); ?>>Vietnamese</option>
                                 <option value="cy" <?php echo  ($val == "cy" ? 'selected' : ''); ?>>Welsh</option>
                                 <option value="xh" <?php echo  ($val == "xh" ? 'selected' : ''); ?>>Xhosa</option>
                                 <option value="yi" <?php echo  ($val == "yi" ? 'selected' : ''); ?>>Yiddish</option>
                                 <option value="yo" <?php echo  ($val == "yo" ? 'selected' : ''); ?>>Yoruba</option>
                                 <option value="zu" <?php echo  ($val == "zu" ? 'selected' : ''); ?>>Zulu</option>
                             </select>                                
                             </td>
                         <td></td>
                         <td><a class="gwt_anchor" id="update_serpdb_engine_language" href="#">update</a></td>
                         <td></td>
                       </tr>
                     </table>            
                 </form>
            </div>

            <div id="serpdb_Searches" class="content">
                <?php echo serpdb_termsTable(); ?>
            </div>
     
            <div id="serpdb_EditSearches" class="content">
                <form action="" method="post">
                    <table>

                       <tr valign="top">
                         <th scope="row"><label for="serpdb_term_recordid" align="left">recordid</label></th>
                         <td id="serpdb_term_recordid"></td>
                         <td><a class="gwt_anchor" id="standard_serpdb_term" href="#">standard</a></td>
                         <td></td>
                         <td></td>
                       </tr>
                       
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_term_term" align="left">term</label></th>
                         <td><input name="serpdb_term_term" type="text" id="serpdb_term_term" value="" /></td>
                         <td><a class="gwt_anchor" id="clear_serpdb_term" href="#">clear</a></td>
                         <td></td>
                         <td></td>
                       </tr>

                       <tr valign="top">
                         <th scope="row"><label for="serpdb_term_char" align="left">char</label></th>
                         <td><input name="serpdb_term_char" type="text" id="serpdb_term_char" value="" /></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>
                       
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_term_depth" align="left">depth</label></th>
                         <td><input name="serpdb_term_depth" type="text" id="serpdb_term_depth" value="" /></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>                  
      
                        <tr valign="top">
                         <th scope="row"><label for="serpdb_term_freq" align="left">freq</label></th>
                         <td><input name="serpdb_term_freq" type="text" id="serpdb_term_freq" value="" /></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>
                        
                       <tr valign="top">
                         <th scope="row"><label for="serpdb_term_nextdate" align="left">next date</label></th>
                         <td><input name="serpdb_term_nextdate" type="text" id="serpdb_term_nextdate" value="" /></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>

                      <tr valign="top">
                         <th scope="row"><label for="serpdb_term_engine_language" align="left">engine</label></th>
                         <td>                                
                             <select name="serpdb_term_engine_language" id="serpdb_term_engine_language">
                                 <option value="af">Afrikaans</option>
                                 <option value="sq">Albanian</option>
                                 <option value="am">Amharic</option>
                                 <option value="ar">Arabic</option>
                                 <option value="hy">Armenian</option>
                                 <option value="az">Azerbaijani</option>
                                 <option value="eu">Basque</option>
                                 <option value="be">Belarusian</option>
                                 <option value="bn">Bengali</option>
                                 <option value="bh">Bihari</option>
                                 <option value="bs">Bosnian</option>
                                 <option value="br">Breton</option>
                                 <option value="bg">Bulgarian</option>
                                 <option value="km">Cambodian</option>
                                 <option value="ca">Catalan</option>
                                 <option value="zh-CN">  Chinese (Simplified)</option>
                                 <option value="zh-TW">  Chinese (Traditional)</option>
                                 <option value="co">Corsican</option>
                                 <option value="hr">Croatian</option>
                                 <option value="cs">Czech</option>
                                 <option value="da">Danish</option>
                                 <option value="nl">Dutch</option>
                                 <option value="en">English</option>
                                 <option value="eo">Esperanto</option>
                                 <option value="et">Estonian</option>
                                 <option value="fo">Faroese</option>
                                 <option value="tl">Filipino</option>
                                 <option value="fi">Finnish</option>
                                 <option value="fr">French</option>
                                 <option value="fy">Frisian</option>
                                 <option value="gl">Galician</option>
                                 <option value="ka">Georgian</option>
                                 <option value="de">German</option>
                                 <option value="el">Greek</option>
                                 <option value="gn">Guarani</option>
                                 <option value="gu">Gujarati</option>
                                 <option value="ha">Hausa</option>
                                 <option value="iw">Hebrew</option>
                                 <option value="hi">Hindi</option>
                                 <option value="hu">Hungarian</option>
                                 <option value="is">Icelandic</option>
                                 <option value="id">Indonesian</option>
                                 <option value="ia">Interlingua</option>
                                 <option value="ga">Irish</option>
                                 <option value="it">Italian</option>
                                 <option value="ja">Japanese</option>
                                 <option value="jw">Javanese</option>
                                 <option value="kn">Kannada</option>
                                 <option value="kk">Kazakh</option>
                                 <option value="rw">Kinyarwanda</option>
                                 <option value="rn">Kirundi</option>
                                 <option value="xx-klingon">Klingon</option>
                                 <option value="ko">Korean</option>
                                 <option value="ku">Kurdish</option>
                                 <option value="ky">Kyrgyz</option>
                                 <option value="lo">Laothian</option>
                                 <option value="la">Latin</option>
                                 <option value="lv">Latvian</option>
                                 <option value="ln">Lingala</option>
                                 <option value="lt">Lithuanian</option>
                                 <option value="mk">Macedonian</option>
                                 <option value="mg">Malagasy</option>
                                 <option value="ms">Malay</option>
                                 <option value="ml">Malayalam</option>
                                 <option value="mt">Maltese</option>
                                 <option value="mi">Maori</option>
                                 <option value="mr">Marathi</option>
                                 <option value="mo">Moldavian</option>
                                 <option value="mn">Mongolian</option>
                                 <option value="sr-ME">  Montenegrin</option>
                                 <option value="ne">Nepali</option>
                                 <option value="no">Norwegian</option>
                                 <option value="nn">Norwegian (Nynorsk)</option>
                                 <option value="oc">Occitan</option>
                                 <option value="or">Oriya</option>
                                 <option value="om">Oromo</option>
                                 <option value="ps">Pashto</option>
                                 <option value="fa">Persian</option>
                                 <option value="pl">Polish</option>
                                 <option value="pt-BR">Portuguese (Brazil)</option>
                                 <option value="pt-PT">Portuguese (Portugal)</option>
                                 <option value="pa">Punjabi</option>
                                 <option value="qu">Quechua</option>
                                 <option value="ro">Romanian</option>
                                 <option value="rm">Romansh</option>
                                 <option value="ru">Russian</option>
                                 <option value="gd">Scots Gaelic</option>
                                 <option value="sr">Serbian</option>
                                 <option value="sh">Serbo-Croatian</option>
                                 <option value="st">Sesotho</option>
                                 <option value="sn">Shona</option>
                                 <option value="sd">Sindhi</option>
                                 <option value="si">Sinhalese</option>
                                 <option value="sk">Slovak</option>
                                 <option value="sl">Slovenian</option>
                                 <option value="so">Somali</option>
                                 <option value="es">Spanish</option>
                                 <option value="su">Sundanese</option>
                                 <option value="sw">Swahili</option>
                                 <option value="sv">Swedish</option>
                                 <option value="tg">Tajik</option>
                                 <option value="ta">Tamil</option>
                                 <option value="tt">Tatar</option>
                                 <option value="te">Telugu</option>
                                 <option value="th">Thai</option>
                                 <option value="ti">Tigrinya</option>
                                 <option value="to">Tonga</option>
                                 <option value="tr">Turkish</option>
                                 <option value="tk">Turkmen</option>
                                 <option value="tw">Twi</option>
                                 <option value="ug">Uighur</option>
                                 <option value="uk">Ukrainian</option>
                                 <option value="ur">Urdu</option>
                                 <option value="uz">Uzbek</option>
                                 <option value="vi">Vietnamese</option>
                                 <option value="cy">Welsh</option>
                                 <option value="xh">Xhosa</option>
                                 <option value="yi">Yiddish</option>
                                 <option value="yo">Yoruba</option>
                                 <option value="zu">Zulu</option>
                             </select>                                
                             </td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>


                       <tr valign="top">
                         <th scope="row"></th>
                         <td></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>
                       
                       <tr valign="top">
                         <th scope="row"></th>
                         <td><a class="gwt_anchor" id="update_serpdb_term" href="#">update</a></td>
                         <td></td>
                         <td></td>
                         <td></td>
                       </tr>
                       
                    </table>            
                </form>
            </div>
        </div>
    </div>
<?
}



/**
 * grabs url content
 *
 * This method grabs url content using the php cUrl
 * library if available, otherwise file_get_contents
 *
 * @param string $url
 * @return string $content
 */    
function serpdb_getPageData($url) {
    
	if(function_exists('curl_init')) {
		$ch = curl_init($url); // initialize curl with given url
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // add useragent
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // write the response to a variable
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // max. seconds to execute
		curl_setopt($ch, CURLOPT_FAILONERROR, 1); // stop when it encounters an error
		$content = @curl_exec($ch);
                curl_close($ch);
	}
	else {
		$content= @file_get_contents($url);
	}
        return $content;
    
}



?>