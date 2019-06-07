<?php
/*
 * CodeMOOC TreasureHuntBot
 * ===================
 * UWiClab, University of Urbino
 * ===================
 * Class wrapping the user's current game.
 */

class Game {

    private $owning_context;

    public  $is_admin = false;

    public  $game_id = null;
    public  $game_name = null;
    public  $game_state = GAME_STATE_ACTIVE;
    public  $game_channel_name = null;
    public  $game_channel_censor = false;
    public  $game_has_timeout = false;
    public  $game_timed_out = false;
    public  $game_language = null;
    public  $location_hints_enabled = false;
    public  $badge_overlay_image = null;

    public  $event_id = null;
    public  $event_name = null;
    public  $event_state = EVENT_STATE_OPEN_FOR_ALL;
    public  $event_channel_name = null;

    // Rows of cluster_id, num_locations, description, force_location_on_enter
    private $game_location_clusters = null;

    public  $group_name = '';
    public  $group_state = STATE_INVALID;
    public  $group_participants = 1;

    function __construct($game_id, $is_admin, $owning_context) {
        $this->owning_context = $owning_context;

        if(!$game_id) {
            $this->load_null();
            return;
        }

        $game_data = db_row_query(sprintf(
            "SELECT `name`, `event_id`, `state`, `telegram_channel`, `telegram_channel_censor_photo`, (`timeout_absolute` IS NOT NULL OR `timeout_interval` IS NOT NULL) AS `has_timeout`, `language`, `location_hints_enabled`, `badge_overlay_image` FROM `games` WHERE `game_id` = %d",
            $game_id
        ));
        if(!$game_data) {
            $this->load_null();
            return;
        }

        // Game exists
        $this->is_admin = $is_admin;
        $this->game_id = (int)$game_id;
        $this->game_name = $game_data[0];
        $this->game_state = (int)$game_data[2];
        $this->event_id = (int)$game_data[1];
        $this->game_channel_name = $game_data[3];
        $this->game_channel_censor = (boolean)$game_data[4];
        $this->game_has_timeout = (boolean)$game_data[5];
        $this->game_language = $game_data[6];
        $this->location_hints_enabled = (boolean)$game_data[7];
        $this->badge_overlay_image = $game_data[8];

        $event_data = db_row_query(sprintf(
            "SELECT `name`, `state`, `telegram_channel` FROM `events` WHERE `event_id` = %d",
            $this->event_id
        ));
        if(!$event_data) {
            $this->load_null();
            return;
        }

        //Event exists
        $this->event_name = $event_data[0];
        $this->event_state = (int)$event_data[1];
        $this->event_channel_name = $event_data[2];

        Logger::debug(sprintf(
            "User in game '%s' (%s), event '%s' (%s), channel '%s', censor %s, language '%s'",
            $this->game_name,
            GAME_STATE_MAP[$this->game_state],
            $this->event_name,
            EVENT_STATE_MAP[$this->event_state],
            $this->game_channel_name,
            b2s($this->game_channel_censor),
            $this->game_language
        ), __FILE__, $this->owning_context);

        $this->load_player_data();
        $this->load_game_clusters();
    }

    /**
     * Set to null state (no active game, no active player).
     */
    private function load_null() {
        $this->is_admin = false;

        $this->game_id = null;
        $this->game_name = null;
        $this->game_state = GAME_STATE_ACTIVE;

        $this->event_id = null;
        $this->event_name = null;

        $this->group_state = STATE_INVALID;
        $this->group_participants = 1;
    }

    /**
     * Load player and group data.
     */
    private function load_player_data() {
        if($this->game_id == null) {
            Logger::warning("Attempting to load player data with null game ID", __FILE__, $this->owning_context);
            return;
        }
        if($this->is_admin) {
            //Not needed for admin users
            return;
        }

        // Load group data, this SHOULD be non-null
        $group_data = db_row_query(sprintf(
            "SELECT `name`, `state`, IF(`timeout_absolute` IS NULL, 0, TIMEDIFF(NOW(), `timeout_absolute`) > 0) AS `timed_out`, `participants_count` FROM `groups` WHERE `group_id` = %d AND `game_id` = %d",
            $this->owning_context->get_internal_id(),
            $this->game_id
        ));
        if($group_data) {
            // We have a group
            $this->group_name = $group_data[0];
            $this->group_state = (int)$group_data[1];
            $this->group_participants = intval($group_data[3]);
            $this->game_timed_out = (boolean)$group_data[2];

            Logger::debug(sprintf(
                "User in registered group '%s', state %s (%d), timed out %s",
                $this->group_name,
                map_state_to_string(STATE_MAP, $this->group_state),
                $this->group_state,
                b2s($this->game_timed_out)
            ), __FILE__, $this->owning_context);
        }
    }

    /**
     * Loads location clusters for the current game.
     */
    private function load_game_clusters() {
        $this->game_location_clusters = db_table_query(sprintf(
            "SELECT `cluster_id`, `num_locations`, `description`, `force_location_on_enter` FROM `game_location_clusters` WHERE `game_id` = %s ORDER BY `cluster_id` ASC",
            $this->game_id
        ));
        Logger::debug(sprintf(
            "Game #%s has %d location clusters",
            $this->game_id,
            count($this->game_location_clusters)
        ), __FILE__, $this->owning_context);
    }

    /**
     * Gets the current game's number of required locations to win.
     */
    public function get_game_num_locations() {
        if($this->game_location_clusters == null) {
            return 0;
        }

        $acc = 0;
        foreach($this->game_location_clusters as $cluster) {
            $acc += intval($cluster[1]);
        }

        return $acc;
    }

    /**
     * Gets the cluster ID for the next location.
     * @param $num_reached_locations Number of reached locations.
     * @return Cluster ID of the next location to fetch or null if no location can/must be reached.
     */
     public function get_next_location_cluster_id($num_reached_locations = 0) {
        if($this->game_location_clusters == null) {
            Logger::warning("Get next location cluster without having clusters", __FILE__, $this->owning_context);
            return null;
        }
        if(count($this->game_location_clusters) == 0) {
            Logger::error("No clusters defined", __FILE__, $this->owning_context);
            return null;
        }

        Logger::debug("Seeking next location for {$num_reached_locations} reached locations", __FILE__, $this->owning_context);

        foreach($this->game_location_clusters as $cluster) {
            if($num_reached_locations < $cluster[1]) {
                Logger::debug("Picking cluster #{$cluster[0]} ({$cluster[2]}) for next location", __FILE__, $this->owning_context);
                return intval($cluster[0]);
            }

            Logger::debug("Skipping cluster #{$cluster[0]} (with {$cluster[1]} locations)", __FILE__, $this->owning_context);
            $num_reached_locations -= intval($cluster[1]);
        }
    }

    /**
     * Gets whether the next location starts a cluster (i.e., is the first location
     * inside a new cluster for the current user).
     * @param $num_reached_locations Number of reached locations.
     * @return True if the next location starts a cluster.
     */
    function next_location_starts_cluster($num_reached_locations = 0) {
        if($this->game_location_clusters == null) {
            Logger::warning("Get next location cluster without having clusters", __FILE__, $this->owning_context);
            return false;
        }
        if(count($this->game_location_clusters) == 0) {
            Logger::error("No clusters defined", __FILE__, $this->owning_context);
            return false;
        }
        if($num_reached_locations == 0) {
            Logger::debug("First cluster does not start a new cluster", __FILE__, $this->owning_context);
            return false;
        }

        foreach($this->game_location_clusters as $cluster) {
            if($num_reached_locations == 0) {
                Logger::debug("Cluster #{$cluster[0]} starts a new cluster", __FILE__, $this->owning_context);
                return true;
            }

            $num_reached_locations -= intval($cluster[1]);
        }

        Logger::debug("Cluster #{$cluster[0]} does not start a new cluster", __FILE__, $this->owning_context);
        return false;
    }

    /**
     * Gets whether the next location's cluster forces a location transmission.
     * @param $num_reached_locations Number of reached locations.
     */
    function cluster_forces_location_on_enter($num_reached_locations) {
        if($this->game_location_clusters == null) {
            Logger::warning("Get next location cluster without having clusters", __FILE__, $this->owning_context);
            return false;
        }
        if(count($this->game_location_clusters) == 0) {
            Logger::error("No clusters defined", __FILE__, $this->owning_context);
            return false;
        }
        if($num_reached_locations == 0) {
            Logger::debug("First cluster does not start a new cluster", __FILE__, $this->owning_context);
            return false;
        }

        foreach($this->game_location_clusters as $cluster) {
            if($num_reached_locations == 0) {
                Logger::debug("Cluster #{$cluster[0]} forces location: {$cluster[3]}", __FILE__, $this->owning_context);
                return (boolean)$cluster[3];
            }

            $num_reached_locations -= intval($cluster[1]);
        }

        Logger::debug("Cluster #{$cluster[0]} does not start a new cluster", __FILE__, $this->owning_context);
        return false;
    }

}
