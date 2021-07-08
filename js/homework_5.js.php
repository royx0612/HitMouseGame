<?php 
  include_once "../ajax/class/CSRF.class.php";
?>
gameComponent = () => {
  return {
    playerName: "",
    gameTime: 60,
    counting: 0, // 時間經過
    hit: 0, // 打擊次數
    score: 0, // 得分
    starting: false,
    showLeaderBoard: false,
    leaderBoardPlayers: [],
    gameInterval: [], // 遊戲計數器
    gameTimeout: [], // 躲下去的 timeout handler
    canHitHoles: [], // 躲下去打不到
    holes: Array.from(Array(9).keys()),
    init() {
      // this.submitData();
      // this.updateLeaderBoard();
    },
    //  遊戲動作 -----------------------------------------------------
    reset() {
      this.starting = false;
      this.counting = 0;
      this.score = 0;
      this.hit = 0;
      clearInterval(this.gameInterval);
      // enable Start Button
      document.getElementById("startButton").disabled = false;
      document.getElementById("startButton").classList.remove("opacity-50");

      //disable Stop Button
      document.getElementById("stopButton").disabled = true;
      document.getElementById("stopButton").classList.add("opacity-50");
      this.allMousesHide();
    },
    start() {
      this.gameInterval = setInterval(() => {
        this.counting += 1;
        if (this.counting >= this.gameTime) {
          this.stop();
          this.submitData();
        }
        this.generateMouses(this.randomNumber(1, Math.ceil(this.counting / 8)));
      }, 1000);
      this.starting = true;

      // disable Start Button
      document.getElementById("startButton").disabled = true;
      document.getElementById("startButton").classList.add("opacity-50");

      // disable Reset Button
      document.getElementById("resetButton").disabled = true;
      document.getElementById("resetButton").classList.add("opacity-50");

      // enable Cancel Button
      document.getElementById("stopButton").disabled = false;
      document.getElementById("stopButton").classList.remove("opacity-50");
      this.playBG();
      this.hideTemplate();
    },
    stop() {
      this.starting = false;
      clearInterval(this.gameInterval);

      //disable Stop Button
      document.getElementById("stopButton").disabled = true;
      document.getElementById("stopButton").classList.add("opacity-50");

      // enable reset Button
      document.getElementById("resetButton").disabled = false;
      document.getElementById("resetButton").classList.remove("opacity-50");
      this.stopBG();
      this.showTemplate();
    },
    userHit(index) {
      if (this.starting) {
        this.hit += 1;
        let image = this.$el.children[0],
          filename = image.src.replace(/^.*[\\\/]/, "");
        console.log(image.style);
        if (filename == "mouse_show.png" && this.canHitHoles[index]) {
          this.score += 1;
          this.mouseHitted(index);
          var audio = new Audio("mp3/punch.mp3");
          audio.play();
        } else {
          var audio = new Audio("mp3/swing.mp3");
          audio.play();
        }
      }
    },

    //  產生老鼠 -----------------------------------------------------
    generateMouses(count) {
      for (let i = 0; i < count; i++) {
        let randomNumber = this.randomNumber(0, 9);
        this.mouseShowUp(randomNumber);
      }
    },
    mouseShowUp(index) {
      let image = document.getElementById("mouse" + index),
        filename = image.src.replace(/^.*[\\\/]/, "");
      if (filename == "mouse_hide.png") {
        image.src = "img/mouse_show.png";
        image.className = "mouseIn";
        this.canHitHoles[index] = true;
        this.gameTimeout[index] = setTimeout(() => {
          this.mouseHide(index);
        }, this.randomNumber(900 - this.counting * 10, 1800 - this.counting * 10));
      }
    },
    mouseHide(index) {
      let image = document.getElementById("mouse" + index),
        filename = image.src.replace(/^.*[\\\/]/, "");
      image.className = "mouseOut";
      this.canHitHoles[index] = false;
      setTimeout(() => {
        image.src = "img/mouse_hide.png";
      }, 700);
    },
    mouseHitted(index) {
      let image = document.getElementById("mouse" + index),
        filename = image.src.replace(/^.*[\\\/]/, "");

      image.src = "img/mouse_hitted.png";
      clearTimeout(this.gameTimeout[index]);
      setTimeout(() => {
        this.mouseHide(index);
      }, 700);
    },
    allMousesHide() {
      for (let i = 0; i < 9; i++) {
        let image = document.getElementById("mouse" + i).classList;
        if (image.contains("mouseHit")) {
          image.remove("mouseHit");
        }
        if (image.contains("mouseShow")) {
          image.remove("mouseShow");
        }
        image.add("mouseHide");
      }
    },
    //  其他 -----------------------------------------------------
    removeClasses(index) {
      let image = document.getElementById("mouse" + index);
      image.className = "";
    },
    randomNumber(min, max) {
      return Math.floor(Math.random() * max) + min;
    },
    playBG() {
      bg = new Audio("mp3/bg.mp3");
      bg.loop = true;
      bg.play();
    },
    stopBG() {
      bg.pause();
    },
    hitRate() {
      return this.hit > 0 ? ((this.score / this.hit) * 100).toFixed(1) : 0;
    },
    hideTemplate() {
      document.getElementById("template").className = "mouseOut";
    },
    showTemplate() {
      document.getElementById("template").className = "mouseIn";
    },
    submitData() {
      fetch("ajax/leaderBoard.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: 'submit',
          player_name: this.playerName,
          score: this.score,
          hit_rate: this.hitRate(),
          csrf:'<?= CSRF::generateToken('csrf_submit') ?>'
        }),
      })
      .then((response) => response.json())
      .then((responseData) => {
        if (responseData.status == "ok") {
          
        } else {
          alert(responseData.message);
        }
      });
    },
    updateLeaderBoard(){
      fetch('ajax/leaderBoard.php', {
        method: "POST",
        headers: { "Content-Type": "application/json"},
        body: JSON.stringify({
          action: 'renew',
          csrf:'<?= CSRF::generateToken('csrf_renew') ?>'
        }),
      })
      .then((response) => response.json())
      .then((responseData) => {
        if (responseData.status == "ok") {
          this.showLeaderBoard = true;
          this.leaderBoardPlayers = responseData.datas;
        } else {
          alert(responseData.message);
        }
      });
    }
  };
};
