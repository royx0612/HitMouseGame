<?php
include "ajax/class/CSRF.class.php";

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet" />
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script src="js/homework_5.js.php"></script>
  <link rel="stylesheet" href="css/homework_5.css">
  <title>function</title>

</head>

<body class="bg-black">
  <div class="container flex flex-col justify-center items-center h-screen mx-auto" x-data="gameComponent">
    <div>
      <div class="my-2 flex justify-between">
        <span class="text-2xl text-red-500" x-text="'剩下時間: '+(gameTime - counting)+' 秒'" x-show="starting"></span>
        <span class="text-2xl text-blue-500" x-text="'命中率: '+ hitRate() +' %'"></span>
        <span class="text-2xl text-yellow-500" x-text="'得分: '+score+' 分'"></span>
      </div>
      <div class="grid grid-cols-3 grid-rows-3 gap-3">
        <template x-for="(index) in holes">
          <div class="border cursor-pointer select-none defaultImage mouseHide" @click="userHit(index)">
            <img src="img/mouse_hide.png" :id="'mouse'+index" \>
            <img src="img/pre_hole.png">
          </div>
        </template>
      </div>
      <div class="w-full text-center flex justify-around mt-6">
        <button class="px-5 py-2 rounded-xl bg-blue-500 text-white border border-gray-300 hover:bg-blue-600" @click="start" id="startButton">遊戲開始</button>
        <button class="px-5 py-2 self-end rounded-xl bg-red-500 text-white border border-gray-300 hover:bg-red-500 opacity-50" @click="stop" id="stopButton" disabled>取消遊戲</button>
        <button class="px-5 py-2 self-end rounded-xl bg-yellow-500 text-white border border-gray-300 hover:bg-yellow-500 " @click="reset" id="resetButton">重新開始</button>
        <button class="px-5 py-2 self-end rounded-xl bg-green-500 text-white border border-gray-300 hover:bg-green-500 " @click="updateLeaderBoard()">排行榜</button>

      </div>
    </div>
    <div x-show="playerName != ''" class="text-yellow-400 w-full text-xl text-center mt-4 ">
      <span class="cursor-pointer" x-text="'玩家名稱: '+playerName" @click="(starting == false) ? playerName='' : ''"></span>
    </div>
    <div class="cursor-pointer defaultImage mouseShow">
      <img src="img/mouse_show.png" id="template">
      <img src="img/pre_hole.png">
    </div>
    <template x-if="playerName.length <= 0 && starting == false">
      <div>
        <div class="z-10 w-screen h-screen bg-black fixed opacity-50 top-0 left-0"></div>
        <div class="z-20 p-4 bg-green-500 w-screen fixed left-0 bottom-0 flex justify-center items-center">
          <span class="text-2xl text-white">玩家名稱</span>
          <input type="text" class="px-2 py-1 ml-2 border border-gray-300 rounded-xl" x-ref="playerName" :value="playerName" autofocus>
          <button class="px-2 py-1 ml-2 bg-gray-600 border text-white rounded hover:bg-blue-600 transition hover:border-blue-500" @click="playerName = $refs.playerName.value">輸入</button>
        </div>
      </div>
    </template>

    <template x-if="showLeaderBoard">
      <div>
        <div class="z-10 w-screen h-screen bg-black fixed opacity-50 top-0 left-0"></div>
        <div class="z-20 p-4 bg-green-800 w-screen fixed left-0 top-0 flex flex-col justify-center items-center">
          <span class="text-2xl text-white font-bold">打地鼠排行榜</span>
          <div class="w-8/12 text-center my-4">
            <table class="table-auto text-white text-xl mx-auto border-collapse border border-green-800">
              <thead>
                <th>排名</th>
                <th>姓名</th>
                <th>得分 / 打擊率</th>
              </thead>
              <template x-for="(row, index) in leaderBoardPlayers">
                <tr>
                  <td x-text="index+1"></td>
                  <td x-text="row.player_name">Royx</td>
                  <td x-text="row.score+' / '+row.hit_rate+' %'">54</td>
                </tr>
              </template>
            </table>
          </div>
          <button class="px-2 py-1 ml-2 bg-gray-600 border text-white rounded hover:bg-green-600 transition hover:border-blue-500" @click="showLeaderBoard=false">確定</button>
        </div>
      </div>
    </template>
  </div>

</body>

</html>