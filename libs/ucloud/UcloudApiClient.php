<?php
/**
 * Created by: Joker
 * Date: 2019/9/11
 * Time: 19:12
 */
namespace app\libs\ucloud;

class UcloudApiClient
{

    function __construct( $base_url, $public_key, $private_key, $project_id)
    {
        $this->conn = new UConnection($base_url);
        $this->public_key = $public_key;
        $this->private_key = $private_key;

        if ($project_id !== "") {
            $this->project_id = $project_id;
        }
    }

    function get($api, $params){
        $params["PublicKey"] = $this->public_key;

        if ( isset($this->project_id) && !empty($this->project_id) )
        {
            $params["ProjectId"] = $this->project_id;
        }
        ksort($params);
        $params_data = "";
        foreach($params as $key => $value){
            $params_data .= $key;
            $params_data .= $value;
        }
        $params_data .= $this->private_key;
        $params["Signature"] =  sha1($params_data);
        return $this->conn->get($api, $params);
    }

    function post($api, $params){
        $params["PublicKey"] = $this->public_key;
        if ( isset($this->project_id) && !empty($this->project_id) )
        {
            $params["ProjectId"] = $this->project_id;
        }
        ksort($params);
        $params_data = "";
        foreach($params as $key => $value){
            $params_data .= $key;
            $params_data .= $value;
        }
        $params_data .= $this->private_key;
        $params["Signature"] =  sha1($params_data);
        return $this->conn->post($api, $params);
    }

    function _verfy_ac($private_key, $params) {

    }

}
