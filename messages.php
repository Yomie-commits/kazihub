<?php
require __DIR__ . '/includes/init.php';
$me = require_login();
$uid = (int) $me['id'];

$withId = (int) (get('with') ?: post('to'));
$partner = $withId && $withId !== $uid ? db_one('SELECT id, name, avatar, role, is_verified, last_seen, status FROM users WHERE id = ?', [$withId]) : null;

// Send (non-JS fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = post('body');
    if (!$partner || $partner['status'] !== 'active') {
        flash('danger', 'You cannot message this user.');
    } elseif ($body === '') {
        flash('warning', 'Type a message first.');
    } else {
        db_insert('messages', ['sender_id' => $uid, 'receiver_id' => $partner['id'], 'body' => mb_substr($body, 0, 4000), 'created_at' => now()]);
    }
    redirect('messages.php', $partner ? ['with' => $partner['id']] : []);
}

// Conversation list
$convos = db_all('SELECT t.partner_id, MAX(t.id) AS last_id,
        SUM(CASE WHEN t.receiver_id = ? AND t.is_read = 0 THEN 1 ELSE 0 END) AS unread
    FROM (SELECT id, receiver_id, is_read, CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END AS partner_id
          FROM messages WHERE sender_id = ? OR receiver_id = ?) t
    GROUP BY t.partner_id ORDER BY last_id DESC LIMIT 100', [$uid, $uid, $uid, $uid]);
$people = [];
if ($convos) {
    $ids = array_column($convos, 'partner_id');
    $lastIds = array_column($convos, 'last_id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    foreach (db_all("SELECT id, name, avatar, last_seen FROM users WHERE id IN ($ph)", $ids) as $p) $people[$p['id']] = $p;
    $lastMsgs = [];
    $ph2 = implode(',', array_fill(0, count($lastIds), '?'));
    foreach (db_all("SELECT id, sender_id, body, created_at FROM messages WHERE id IN ($ph2)", $lastIds) as $m) $lastMsgs[$m['id']] = $m;
}

$thread = [];
if ($partner) {
    db_exec('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0', [$partner['id'], $uid]);
    $thread = db_all('SELECT * FROM (SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY id DESC LIMIT 200) m ORDER BY id ASC', [$uid, $partner['id'], $partner['id'], $uid]);
}

$pageTitle = 'Messages';
require __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <h1 class="h3 fw-bold mb-3">Messages</h1>
    <div class="card overflow-hidden">
        <div class="row g-0">
            <div class="col-md-4 border-end <?= $partner ? 'd-none d-md-block' : '' ?>">
                <div class="list-group list-group-flush chat-list">
                    <?php if (!$convos && !$partner): ?>
                        <?= empty_state('bi-chat-dots', 'No messages yet', 'Start a conversation from someone’s profile, a service or a job.') ?>
                    <?php endif; ?>
                    <?php if ($partner && !isset($people[$partner['id']])): ?>
                        <a href="#" class="list-group-item list-group-item-action active d-flex gap-2 align-items-center py-3">
                            <?= avatar($partner, 42) ?><div class="fw-semibold"><?= e($partner['name']) ?><div class="small text-body-secondary fw-normal">New conversation</div></div>
                        </a>
                    <?php endif; ?>
                    <?php foreach ($convos as $c):
                        $p = $people[$c['partner_id']] ?? null;
                        if (!$p) continue;
                        $last = $lastMsgs[$c['last_id']] ?? null; ?>
                        <a href="<?= e(url('messages.php', ['with' => $p['id']])) ?>" class="list-group-item list-group-item-action d-flex gap-2 align-items-center py-3 <?= $partner && (int) $partner['id'] === (int) $p['id'] ? 'active' : '' ?>">
                            <div class="position-relative"><?= avatar($p, 42) ?><?php if (is_online($p['last_seen'])): ?><span class="online-dot position-absolute bottom-0 end-0"></span><?php endif; ?></div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex justify-content-between">
                                    <span class="fw-semibold text-truncate"><?= e($p['name']) ?></span>
                                    <small class="text-body-secondary text-nowrap ms-2"><?= e(time_ago($last['created_at'] ?? null)) ?></small>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-body-secondary text-truncate"><?= $last && (int) $last['sender_id'] === $uid ? 'You: ' : '' ?><?= e(excerpt($last['body'] ?? '', 60)) ?></small>
                                    <?php if ($c['unread'] > 0): ?><span class="badge rounded-pill text-bg-danger ms-2"><?= (int) $c['unread'] ?></span><?php endif; ?>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-8 <?= $partner ? '' : 'd-none d-md-block' ?>">
                <?php if (!$partner): ?>
                    <div class="d-flex align-items-center justify-content-center h-100 py-5"><?= empty_state('bi-chat-left-text', 'Select a conversation') ?></div>
                <?php else: ?>
                    <div class="d-flex align-items-center gap-2 p-3 border-bottom bg-white">
                        <a href="<?= e(url('messages.php')) ?>" class="btn btn-sm btn-light d-md-none"><i class="bi bi-arrow-left"></i></a>
                        <?= avatar($partner, 40) ?>
                        <div>
                            <a href="<?= e(url('user.php', ['id' => $partner['id']])) ?>" class="fw-semibold text-body text-decoration-none"><?= e($partner['name']) ?></a>
                            <?php if ($partner['is_verified']): ?><i class="bi bi-patch-check-fill text-brand"></i><?php endif; ?>
                            <div class="small text-body-secondary"><?= is_online($partner['last_seen']) ? '<span class="online-dot"></span> Online' : 'Last seen ' . e(time_ago($partner['last_seen']) ?: 'a while ago') ?></div>
                        </div>
                    </div>
                    <div class="chat-window p-3" id="chatWindow" data-partner="<?= (int) $partner['id'] ?>" data-last-id="<?= $thread ? (int) end($thread)['id'] : 0 ?>">
                        <?php if (!$thread): ?><p class="text-center text-body-secondary small my-5" id="chatEmpty">Say hello 👋 Keep payments and agreements on KaziHub for your safety.</p><?php endif; ?>
                        <?php foreach ($thread as $m): ?>
                            <div class="bubble <?= (int) $m['sender_id'] === $uid ? 'me' : 'them' ?>"><?= e($m['body']) ?><time><?= e(date('j M, H:i', strtotime($m['created_at']))) ?></time></div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($partner['status'] === 'active'): ?>
                    <form method="post" class="d-flex gap-2 p-3 border-top bg-white" id="chatForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="to" value="<?= (int) $partner['id'] ?>">
                        <textarea class="form-control" name="body" id="chatInput" rows="1" placeholder="Write a message…" required maxlength="4000" style="resize:none"></textarea>
                        <button class="btn btn-brand px-3" aria-label="Send"><i class="bi bi-send-fill"></i></button>
                    </form>
                    <?php else: ?>
                        <div class="p-3 border-top small text-body-secondary">This account is no longer active.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
if ($partner) {
    $pageScript = '<script>
(function(){
  var win = document.getElementById("chatWindow"), form = document.getElementById("chatForm"), input = document.getElementById("chatInput");
  var api = ' . json_encode(url('messages-api.php')) . ', partner = win.dataset.partner, csrf = document.querySelector("meta[name=csrf-token]").content;
  win.scrollTop = win.scrollHeight;
  function esc(s){var d=document.createElement("div");d.textContent=s;return d.innerHTML;}
  function add(list){
    var empty = document.getElementById("chatEmpty"); if (empty && list.length) empty.remove();
    var atBottom = win.scrollHeight - win.scrollTop - win.clientHeight < 80;
    list.forEach(function(m){
      if (+m.id <= +win.dataset.lastId) return;
      win.insertAdjacentHTML("beforeend", "<div class=\"bubble " + (m.mine ? "me" : "them") + "\">" + esc(m.body) + "<time>" + esc(m.time) + "</time></div>");
      win.dataset.lastId = m.id;
    });
    if (atBottom || (list.length && list[list.length-1].mine)) win.scrollTop = win.scrollHeight;
  }
  function poll(){
    fetch(api + "?with=" + partner + "&after=" + win.dataset.lastId, {credentials: "same-origin"})
      .then(function(r){ return r.ok ? r.json() : []; }).then(add).catch(function(){});
  }
  setInterval(poll, 5000);
  if (form) {
    input.addEventListener("keydown", function(e){ if (e.key === "Enter" && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
    form.addEventListener("submit", function(e){
      e.preventDefault();
      var body = input.value.trim(); if (!body) return;
      var fd = new FormData(); fd.append("to", partner); fd.append("body", body); fd.append("after", win.dataset.lastId);
      input.value = "";
      fetch(api, {method: "POST", body: fd, credentials: "same-origin", headers: {"X-CSRF-Token": csrf}})
        .then(function(r){ if (!r.ok) throw r; return r.json(); }).then(add)
        .catch(function(){ input.value = body; alert("Message not sent. Please try again."); });
    });
  }
})();
</script>';
}
require __DIR__ . '/includes/footer.php';
