<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
 
 
 
// There are several virtual page classes, we want to avoid a clash!
//
//

if (!class_exists('WP_EX_PAGE_ON_THE_FLY')){
    /**
    * WP_EX_PAGE_ON_THE_FLY
    * @author Ohad Raz
    * @since 0.1
    * Class to create pages "On the FLY"
    * Usage: 
    *   $args = array(
    *       'slug' => 'fake_slug',
    *       'post_title' => 'Fake Page Title',
    *       'post content' => 'This is the fake page content'
    *   );
    *   new WP_EX_PAGE_ON_THE_FLY($args);
    */
    class WP_EX_PAGE_ON_THE_FLY
    {

        public $slug ='';
        public $args = array();
        /**
         * __construct
         * @param array $arg post to create on the fly
         * @author Ohad Raz 
         * 
         */
        function __construct($args){
            add_filter('the_posts',array($this,'fly_page'));
            $this->args = $args;
            $this->slug = $args['slug'];
        }

        /**
         * fly_page 
         * the Money function that catches the request and returns the page as if it was retrieved from the database
         * @param  array $posts 
         * @return array 
         * @author Ohad Raz
         */
        public function fly_page($posts){
            global $wp,$wp_query;
            $page_slug = $this->slug;

            //check if user is requesting our fake page
            if(count($posts) == 0 && (strtolower($wp->request) == $page_slug || ( isset($wp->query_vars['page_id']) && $wp->query_vars['page_id'] == $page_slug ) )){

                //create a fake post
                $post = new stdClass;
                $post->post_author = 1;
                $post->post_name = $page_slug;
                $post->guid = get_bloginfo('wpurl' . '/' . $page_slug);
                $post->post_title = 'page title';
                //put your custom content here
                $post->post_content = "Fake Content";
                //just needs to be a number - negatives are fine
                $post->ID = -42;
                $post->post_status = 'static';
                $post->comment_status = 'closed';
                $post->ping_status = 'closed';
                $post->comment_count = 0;
                //dates may need to be overwritten if you have a "recent posts" widget or similar - set to whatever you want
                $post->post_date = current_time('mysql');
                $post->post_date_gmt = current_time('mysql',1);

                $post = (object) array_merge((array) $post, (array) $this->args);
                $posts = NULL;
                $posts[] = $post;

                $wp_query->is_page = true;
                $wp_query->is_singular = true;
                $wp_query->is_home = false;
                $wp_query->is_archive = false;
                $wp_query->is_category = false;
                unset($wp_query->query["error"]);
                $wp_query->query_vars["error"]="";
                $wp_query->is_404 = false;
            }

            return $posts;
        }
    }//end class
}//end if

?>