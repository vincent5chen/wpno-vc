/**
 * 阅读页：阅读记录、日/夜模式、底部菜单、朗读
 */
(function($){
  'use strict';

  var Reading = {
    init: function(){
      if (!$('#single').length) return;
      this.bindScrollToolbar();
      this.bindModePopup();
      this.bindTts();
      this.initMode();
      this.saveProgress();
      this.resumeTtsIfNeeded();
    },

    saveProgress: function(){
      if (!window.wpnovcReading || !wpnovcReading.bookId || !wpnovcReading.postId) return;
      $.post(wpnovcReading.ajaxUrl, {
        action: 'save_read_process',
        nonce: wpnovcReading.nonce,
        postId: wpnovcReading.postId,
        book_id: wpnovcReading.bookId,
        currentPage: wpnovcReading.page || 1
      });
    },

    bindScrollToolbar: function(){
      var toolbar = $('#reading-toolbar');
      if (toolbar.data('fixed')) return;
      var last = $(window).scrollTop();
      $(window).on('scroll', function(){
        var now = $(this).scrollTop();
        if (now > last && now > 80) {
          toolbar.addClass('hidden');
        } else {
          toolbar.removeClass('hidden');
        }
        last = now;
      });
    },

    initMode: function(){
      var mode = this.getMode();
      this.applyMode(mode);
      this.updateModeBtn(mode);
    },

    getMode: function(){
      if (window.wpnovcReading && wpnovcReading.userMode) return wpnovcReading.userMode;
      return localStorage.getItem('wpnovc_reading_mode') || 'day';
    },

    bindModePopup: function(){
      var self = this;
      var popup = $('#reading-mode-popup');
      $('#reading-mode-btn').on('click', function(){
        popup.toggle();
      });
      $(document).on('click', function(e){
        if (!$(e.target).closest('#reading-mode-btn, #reading-mode-popup').length) popup.hide();
      });
      $('.reading-mode-option').on('click', function(){
        var mode = $(this).data('mode');
        self.applyMode(mode);
        self.saveMode(mode);
        self.updateModeBtn(mode);
        popup.hide();
      });
    },

    applyMode: function(mode){
      $('body').removeClass('reading-day reading-night-black reading-night-kindle');
      var cls = mode === 'night-black' ? 'reading-night-black' : (mode === 'night-kindle' ? 'reading-night-kindle' : 'reading-day');
      $('body').addClass(cls);
      localStorage.setItem('wpnovc_reading_mode', mode);
    },

    saveMode: function(mode){
      if (!window.wpnovcReading || !wpnovcReading.nonce) return;
      $.post(wpnovcReading.ajaxUrl, {
        action: 'save_reading_mode',
        nonce: wpnovcReading.nonce,
        mode: mode
      });
    },

    updateModeBtn: function(mode){
      var label = mode === 'day' ? '日间' : (mode === 'night-black' ? '纯黑夜间' : 'Kindle夜间');
      $('#reading-mode-btn').text(label);
    },

    bindTts: function(){
      var self = this;
      $('#reading-tts-btn').on('click', function(){
        self.toggleTts();
      });
    },

    toggleTts: function(){
      var synth = window.speechSynthesis;
      if (!synth) { alert('当前浏览器不支持朗读功能'); return; }
      if (synth.speaking) {
        synth.cancel();
        sessionStorage.removeItem('wpnovc_tts_playing');
        this.updateTtsBtn(false);
        return;
      }
      sessionStorage.setItem('wpnovc_tts_playing', '1');
      this.updateTtsBtn(true);
      this.speakCurrentChapter();
    },

    speakCurrentChapter: function(){
      var synth = window.speechSynthesis;
      var text = this.getChapterText();
      if (!text) {
        sessionStorage.removeItem('wpnovc_tts_playing');
        this.updateTtsBtn(false);
        return;
      }
      var utter = new SpeechSynthesisUtterance(text);
      utter.lang = 'zh-CN';
      utter.rate = 0.9;
      var voices = synth.getVoices();
      var zh = voices.filter(function(v){ return v.lang && v.lang.indexOf('zh') === 0; })[0];
      if (zh) utter.voice = zh;
      var self = this;
      utter.onend = function(){
        var next = window.wpnovcReading && wpnovcReading.nextUrl;
        if (next) {
          window.location.href = next;
        } else {
          sessionStorage.removeItem('wpnovc_tts_playing');
          self.updateTtsBtn(false);
        }
      };
      utter.onerror = function(){
        sessionStorage.removeItem('wpnovc_tts_playing');
        self.updateTtsBtn(false);
      };
      synth.speak(utter);
    },

    resumeTtsIfNeeded: function(){
      if (sessionStorage.getItem('wpnovc_tts_playing') === '1') {
        var self = this;
        this.updateTtsBtn(true);
        setTimeout(function(){ self.speakCurrentChapter(); }, 500);
      }
    },

    getChapterText: function(){
      var title = $('#single .single-title').first().text();
      var body = $('#single .content-body, #single .content').first().text();
      return (title + '。' + body).replace(/\s+/g, ' ').trim();
    },

    updateTtsBtn: function(playing){
      $('#reading-tts-btn').text(playing ? '暂停' : '朗读');
    }
  };

  $(document).ready(function(){ Reading.init(); });
})(jQuery);
