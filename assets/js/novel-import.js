/**
 * 小说导入 - 分步 AJAX 上传 + 批量处理
 * 避免 Cloudflare 100s 代理超时
 */
(function($) {
    'use strict';

    var NovelImport = {
        fileId: null,
        totalChapters: 0,
        batchSize: 30,
        offset: 0,
        running: false,
        aborted: false,

        init: function() {
            var self = this;
            $('#novel-import-form').on('submit', function(e) {
                e.preventDefault();
                if (self.running) return;
                self.start();
            });
            $('#novel-import-cancel').on('click', function() {
                self.cancel();
            });
        },

        start: function() {
            var self = this;
            var fileInput = $('#novel_file')[0];
            if (!fileInput || !fileInput.files || !fileInput.files[0]) {
                this.showError('请选择 TXT 文件');
                return;
            }
            var parentId = $('#parent_id').val();
            if (!parentId || parentId === '0' || parentId === '-1') {
                this.showError('请选择所属分类');
                return;
            }

            this.running = true;
            this.aborted = false;
            this.offset = 0;
            this.showProgress();
            this.setStatus('正在上传文件...');
            this.updateBar(0, 'uploading');

            var formData = new FormData();
            formData.append('action', 'wpnovc_upload_novel');
            formData.append('nonce', admin_ajax.nonce);
            formData.append('novel_file', fileInput.files[0]);

            $.ajax({
                url: admin_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 300000,
                success: function(res) {
                    if (!res.success) {
                        self.showError(res.data && res.data.msg ? res.data.msg : '上传失败');
                        return;
                    }
                    self.fileId = res.data.file_id;
                    self.totalChapters = res.data.total_chapters;
                    self.setStatus('解析完成: ' + res.data.novel_name +
                        (res.data.author ? ' / 作者: ' + res.data.author : '') +
                        ' / 共 ' + self.totalChapters + ' 章');
                    $('#import-novel-name').text(res.data.novel_name);
                    $('#import-total-chapters').text(self.totalChapters);
                    self.processBatch();
                },
                error: function(xhr, status, err) {
                    var msg = '上传失败';
                    if (status === 'timeout') msg = '上传超时，请检查网络或尝试拆分文件';
                    self.showError(msg + ': ' + err);
                }
            });
        },

        processBatch: function() {
            var self = this;
            if (this.aborted) return;

            var parentId = $('#parent_id').val();
            this.setStatus('正在导入章节 (' + (this.offset + 1) + ' / ' + this.totalChapters + ')...');
            this.updateBar(this.offset, 'processing');

            $.ajax({
                url: admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpnovc_process_chapters',
                    nonce: admin_ajax.nonce,
                    file_id: this.fileId,
                    parent_id: parentId,
                    offset: this.offset,
                    batch_size: this.batchSize
                },
                dataType: 'json',
                timeout: 120000,
                success: function(res) {
                    if (!res.success) {
                        self.showError(res.data && res.data.msg ? res.data.msg : '处理失败');
                        return;
                    }
                    self.offset = res.data.processed;
                    var pct = self.totalChapters > 0 ? Math.round(self.offset / self.totalChapters * 100) : 100;
                    self.updateBar(self.offset, 'processing');

                    if (res.data.done) {
                        self.onComplete(res.data);
                    } else {
                        self.setStatus('已导入 ' + self.offset + ' / ' + self.totalChapters +
                            ' 章 (' + pct + '%)，本批导入 ' + res.data.imported + ' 章');
                        setTimeout(function() { self.processBatch(); }, 500);
                    }
                },
                error: function(xhr, status, err) {
                    var msg = '处理失败';
                    if (status === 'timeout') msg = '处理超时，将自动重试...';
                    self.setStatus(msg + ' (' + self.offset + '/' + self.totalChapters + ')');
                    setTimeout(function() { self.processBatch(); }, 2000);
                }
            });
        },

        onComplete: function(data) {
            var self = this;
            this.running = false;
            this.updateBar(this.totalChapters, 'done');

            var msg = '导入完成！小说: ' + data.novel_name +
                '，共 ' + data.total + ' 章，导入 ' + data.imported + ' 章';
            if (data.skipped > 0) msg += '，跳过 ' + data.skipped + ' 章（已存在）';
            this.setStatus(msg);

            // 清理临时文件
            $.ajax({
                url: admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpnovc_import_cleanup',
                    nonce: admin_ajax.nonce,
                    file_id: this.fileId
                },
                dataType: 'json'
            });

            $('#import-result').html(
                '<p style="color:#2271b1;font-size:14px">' + msg + '</p>' +
                '<p><a href="' + admin_ajax.home_url + '/wp-admin/edit.php?cat=' + data.novel_id +
                '" class="button">查看章节列表</a></p>'
            ).show();
            $('#import-reset').show();
        },

        cancel: function() {
            this.aborted = true;
            this.running = false;
            if (this.fileId) {
                $.ajax({
                    url: admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpnovc_import_cleanup',
                        nonce: admin_ajax.nonce,
                        file_id: this.fileId
                    },
                    dataType: 'json'
                });
            }
            this.showForm();
            this.setStatus('已取消');
        },

        showProgress: function() {
            $('#novel-import-form').hide();
            $('#import-progress').show();
            $('#import-error').hide();
            $('#import-result').hide();
            $('#import-reset').hide();
        },

        showForm: function() {
            $('#novel-import-form').show();
            $('#import-progress').hide();
            $('#import-error').hide();
        },

        showError: function(msg) {
            this.running = false;
            $('#import-error').text(msg).show();
            $('#import-progress').hide();
            $('#novel-import-form').show();
            $('#import-reset').show();
        },

        setStatus: function(msg) {
            $('#import-status').text(msg);
        },

        updateBar: function(current, state) {
            var pct = this.totalChapters > 0 ? Math.round(current / this.totalChapters * 100) : 0;
            $('#import-bar-fill').css('width', pct + '%').text(pct + '%');
            $('#import-bar-fill').removeClass('uploading processing done').addClass(state);
        }
    };

    var BatchImport = {
        files: [],
        uploaded: [],
        uploadIndex: 0,
        currentFile: 0,
        currentOffset: 0,
        batchSize: 30,
        running: false,
        aborted: false,
        errors: [],

        init: function() {
            var self = this;
            $('#novel-batch-form').on('submit', function(e) {
                e.preventDefault();
                if (self.running) return;
                self.start();
            });
            $('#batch-cancel').on('click', function() {
                self.cancel();
            });
        },

        escapeText: function(s) {
            return $('<div>').text(s == null ? '' : s).html();
        },

        logError: function(novel, msg) {
            this.errors.push({ novel: novel, msg: msg });
        },

        renderErrors: function() {
            var self = this;
            if (!this.errors.length) {
                $('#batch-error-log').hide().empty();
                return;
            }
            var html = '<strong>错误明细</strong><ul style="margin:6px 0 0">';
            this.errors.forEach(function(e) {
                html += '<li><strong>' + self.escapeText(e.novel) + '</strong>：' + self.escapeText(e.msg) + '</li>';
            });
            html += '</ul>';
            $('#batch-error-log').html(html).show();
        },

        start: function() {
            var self = this;
            if (typeof admin_ajax === 'undefined') {
                this.showError('页面脚本未正确加载，请强制刷新浏览器（Ctrl+Shift+R）后重试');
                return;
            }
            var fileInput = $('#batch_files')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                this.showError('请选择至少一个 TXT 文件');
                return;
            }
            var parentId = $('#batch_parent_id').val();
            if (!parentId || parentId === '0' || parentId === '-1') {
                this.showError('请选择所属分类');
                return;
            }
            this.files = Array.from(fileInput.files);
            this.uploaded = [];
            this.uploadIndex = 0;
            this.currentFile = 0;
            this.currentOffset = 0;
            this.errors = [];
            this.running = true;
            this.aborted = false;
            this.showProgress();
            $('#batch-error-log').hide().empty();
            this.setStatus('准备批量上传...');
            this.updateBar(0, 'uploading');
            this.uploadNext();
        },

        uploadNext: function() {
            var self = this;
            if (this.aborted) return;
            if (this.uploadIndex >= this.files.length) {
                this.setStatus('上传完成，开始导入小说...');
                this.currentFile = 0;
                this.importNextFile();
                return;
            }
            var file = this.files[this.uploadIndex];
            this.setStatus('正在上传文件 ' + (this.uploadIndex + 1) + ' / ' + this.files.length + '：' + file.name);
            this.setSubStatus('上传阶段');
            var fd = new FormData();
            fd.append('action', 'wpnovc_upload_novel');
            fd.append('nonce', admin_ajax.nonce);
            fd.append('novel_file', file);
            $.ajax({
                url: admin_ajax.ajax_url,
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                timeout: 300000,
                success: function(res) {
                    if (!res.success) {
                        var msg = res.data && res.data.msg ? res.data.msg : '未知错误';
                        self.logError(file.name, msg);
                        self.setSubStatus('文件上传失败：' + file.name + '，已跳过');
                    } else {
                        self.uploaded.push(res.data);
                        self.setSubStatus('已上传 ' + self.uploaded.length + ' 个文件');
                    }
                    self.uploadIndex++;
                    self.updateBar(self.uploadIndex / self.files.length * 100, 'uploading');
                    setTimeout(function() { self.uploadNext(); }, 200);
                },
                error: function(xhr, status, err) {
                    self.logError(file.name, '上传失败：' + (status || '网络错误'));
                    self.setSubStatus('文件上传失败（' + file.name + '），已跳过');
                    self.uploadIndex++;
                    self.updateBar(self.uploadIndex / self.files.length * 100, 'uploading');
                    setTimeout(function() { self.uploadNext(); }, 200);
                }
            });
        },

        importNextFile: function() {
            var self = this;
            if (this.aborted) return;
            if (this.uploaded.length === 0) {
                this.onComplete();
                return;
            }
            if (this.currentFile >= this.uploaded.length) {
                this.onComplete();
                return;
            }
            var item = this.uploaded[this.currentFile];
            this.currentOffset = 0;
            item._retries = 0;
            this.setStatus('正在导入第 ' + (this.currentFile + 1) + ' / ' + this.uploaded.length + ' 本：' + item.novel_name);
            this.processBatch(item);
        },

        processBatch: function(item) {
            var self = this;
            if (this.aborted) return;
            var parentId = $('#batch_parent_id').val();
            this.setSubStatus('章节导入中 ' + (this.currentOffset + 1) + ' / ' + (item.total_chapters || 0));
            $.ajax({
                url: admin_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'wpnovc_process_chapters',
                    nonce: admin_ajax.nonce,
                    file_id: item.file_id,
                    parent_id: parentId,
                    offset: this.currentOffset,
                    batch_size: this.batchSize
                },
                dataType: 'json',
                timeout: 120000,
                success: function(res) {
                    if (!res.success) {
                        var msg = res.data && res.data.msg ? res.data.msg : '未知错误';
                        self.logError(item.novel_name, msg);
                        self.setSubStatus('第 ' + (self.currentFile + 1) + ' 本导入失败，继续下一本...');
                        self.currentFile++;
                        self.importNextFile();
                        return;
                    }
                    self.currentOffset = res.data.processed;
                    if (res.data.done) {
                        self.setSubStatus('第 ' + (self.currentFile + 1) + ' 本导入完成：导入 ' + (res.data.imported || 0) + ' 章，跳过 ' + (res.data.skipped || 0) + ' 章');
                        self.currentFile++;
                        var pct = self.uploaded.length > 0 ? self.currentFile / self.uploaded.length * 100 : 100;
                        self.updateBar(pct, 'processing');
                        setTimeout(function() { self.importNextFile(); }, 300);
                    } else {
                        self.updateBar(
                            (self.currentFile + self.currentOffset / (item.total_chapters || 1)) / self.uploaded.length * 100,
                            'processing'
                        );
                        setTimeout(function() { self.processBatch(item); }, 300);
                    }
                },
                error: function(xhr, status, err) {
                    item._retries = (item._retries || 0) + 1;
                    if (item._retries >= 3) {
                        self.logError(item.novel_name, '章节导入失败：' + (status || '网络错误'));
                        self.setSubStatus('第 ' + (self.currentFile + 1) + ' 本导入失败，继续下一本...');
                        self.currentFile++;
                        self.importNextFile();
                    } else {
                        self.setSubStatus('章节导入失败，重试 ' + item._retries + '/2...');
                        setTimeout(function() { self.processBatch(item); }, 2000);
                    }
                }
            });
        },

        onComplete: function() {
            this.running = false;
            this.updateBar(100, 'done');
            this.setStatus('批量导入完成');
            var msg = '批量导入完成：共处理 ' + this.uploaded.length + ' 本小说';
            if (this.errors.length) {
                msg += '，失败 ' + this.errors.length + ' 本';
            }
            this.setSubStatus(msg);
            $('#batch-result').html(
                '<p style="color:#2271b1;font-size:14px">' + msg + '</p>'
            ).show();
            $('#batch-reset').show();
            this.renderErrors();
            this.cleanupAll();
        },

        cleanupAll: function() {
            this.uploaded.forEach(function(item) {
                $.ajax({
                    url: admin_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'wpnovc_import_cleanup',
                        nonce: admin_ajax.nonce,
                        file_id: item.file_id
                    },
                    dataType: 'json'
                });
            });
        },

        cancel: function() {
            this.aborted = true;
            this.running = false;
            this.cleanupAll();
            this.showForm();
            this.setStatus('已取消');
        },

        showProgress: function() {
            $('#novel-batch-form').hide();
            $('#batch-progress').show();
            $('#batch-error').hide();
            $('#batch-error-log').hide();
            $('#batch-result').hide();
            $('#batch-reset').hide();
        },

        showForm: function() {
            $('#novel-batch-form').show();
            $('#batch-progress').hide();
            $('#batch-error').hide();
            $('#batch-error-log').hide();
        },

        showError: function(msg) {
            this.running = false;
            $('#batch-error').text(msg).show();
            $('#batch-progress').hide();
            $('#novel-batch-form').show();
            $('#batch-reset').show();
        },

        setStatus: function(msg) {
            $('#batch-status').text(msg);
        },

        setSubStatus: function(msg) {
            $('#batch-sub-status').text(msg);
        },

        updateBar: function(pct, state) {
            pct = Math.max(0, Math.min(100, Math.round(pct)));
            $('#batch-bar-fill').css('width', pct + '%').text(pct + '%');
            $('#batch-bar-fill').removeClass('uploading processing done').addClass(state);
        }
    };

    $(document).ready(function() {
        if ($('#novel-import-form').length) {
            NovelImport.init();
        }
        if ($('#novel-batch-form').length) {
            BatchImport.init();
        }
    });

})(jQuery);
