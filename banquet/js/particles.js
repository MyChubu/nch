window.addEventListener('load', function(){
  particlesJS("snow_particlesjs", { //HTMLのidを指定
    "particles": {
      //シェイプの設定
      "number": {
        "value": 150, //シェイプの数
        "density": {
          "enable": true, //シェイプの密集度の設定許可
          "value_area": 800 //シェイプの密集度
        }
      },
      "shape": {
        "type": "image", //circle、edge、triangle、polygon、star、image から選べる
        "image": {
          "src": "https://kakechimaru.com/wp-content/themes/kakechimaru/image/snow_particlesjs.png", //雪の画像を設定する ※相対パスでも可
        }
      },
      "opacity": {
        "value": 0.7, //透明度
        "random": false, //透明度ランダムの許可
        "anim": {
          "enable": false, //透明度のアニメーションさせるかどうか
          "speed": 1, //アニメーションのスピード
          "opacity_min": 0.1, //透明度の最小値
          "sync": false //シェイプを同時にアニメーションさせるかどうか
        }
      },
      "size": {
        "value": 5, //シェイプの大きさ
        "random": true, //大きさランダムの許可
        "anim": {
          "enable": false, //大きさアニメーションさせるかどうか
          "speed": 20, //スピード
          "size_min": 0.1, //スピードの最小値
          "sync": false //同時にアニメーションさせるかどうか
        }
      },
      // 線の設定
      "line_linked": {
        "enable": false, //線を表示するかどうか
      },
      // 動きの設定
      "move": {
        "enable": true, //シェイプの動かすかどうか
        "speed": 2, //スピード
        "direction": "bottom", //シェイプの動く方向(none、top、top-right、right、bottom-right、bottom、bottom-left、left、top-left から選べる)
        "random": true, // 動きはランダム
        "straight": false, // 動きをとどめない
        "out_mode": "out", //エリア外に出たシェイプの動き(out、bounce から選べる)
        "bounce": false, //跳ね返りなし
        "attract": {
          "enable": true,
          "rotateX": 300,
          "rotateY": 1200
        }
      }
    },
    "interactivity": {
      "detect_on": "canvas",
      "events": {
        //マウスオーバー時
        "onhover": {
          "enable": false, //マウスオーバーの許可
        },
        //クリック時
        "onclick": {
          "enable": false, //クッリクの許可
        },
        "resize": true
      }
    },
    "retina_detect": true
  });
});