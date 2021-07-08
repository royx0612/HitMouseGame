<?php
include "class/Database.php";
include "class/CSRF.class.php";

$contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';

if ($contentType === "application/json") {
  //Receive the RAW post data.
  $content = trim(file_get_contents("php://input"));
  $decoded = json_decode($content, true);

  //If json_decode failed, the JSON is invalid.
  if (is_array($decoded)) {
    // my defined data type , should be like submit or renew
    $action = $decoded['action'];

    switch ($action) {
      case 'submit':
        $playerName = $decoded['player_name'];
        $score = $decoded['score'];
        $hitRate = $decoded['hit_rate'];
        $csrf = $decoded['csrf'];

        // CSRF Check
        if (CSRF::validateToken('csrf_submit', $csrf) == false) {
          print json_encode([
            'status' => 'error',
            'message' => 'CSRF ERROR'
          ]);
        } else {
          $db = new Database('phpuser07', 'phpuser07', 'phpuser07_db');
          $db->setTable('leader_boards');
          $db->setCreate([
            'player_name' => $decoded['player_name'],
            'score' => $decoded['score'],
            'hit_rate' => $decoded['hit_rate'],
            'created_at' => date('Y-m-d H:i:s')
          ]);
          $db->execute('create');

          print json_encode([
            'status' => 'ok'
          ]);
        }
        break;


      case 'renew':
        $csrf = $decoded['csrf'];
        // CSRF Check
        if (CSRF::validateToken('csrf_renew', $csrf) == false) {
          print json_encode([
            'status' => 'error',
            'message' => 'CSRF ERROR'
          ]);
        } else {
          $db = new Database('phpuser07', 'phpuser07', 'phpuser07_db');
          $db->setTable('leader_boards');
          $db->setOrderBy([
            'score' => 'DESC',
            'hit_rate' => 'DESC'
          ]);
          $db->setLimit(0, 10);
          $rows = $db->fetchAll();
          print json_encode([
            'status' => 'ok',
            'datas' => $rows,
            'message' => ''
          ]);
        }
        break;

      default:
        print json_encode([
          'status' => 'error',
          'message' => 'Unknow action'
        ]);
        break;
    }

    // No data inside
  } else {
    print json_encode([
      'status' => 'error',
      'message' => 'No data'
    ]);
  }
}
