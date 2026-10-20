<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$error = null;
$saved = isset($_GET['saved']);

// Anyone can take the test and save a result. Everyone gets the same
// dropdown of keyboards from the collection; picking "— none / not sure —"
// reveals two free-text fields instead, for a guest's own gear.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_result') {
    if (!csrf_valid()) {
        $error = 'Your session expired — try running the test again.';
    } else {
        $wpm = (int)($_POST['wpm'] ?? 0);
        $acc = (int)($_POST['acc'] ?? 0);
        $raw = (int)($_POST['raw'] ?? 0);
        $dur = (int)($_POST['dur'] ?? 0);

        if ($wpm <= 0 || $dur <= 0) {
            $error = 'That result looks invalid — try the test again.';
        } else {
            $keyboardId = null;
            $guestModel = null;
            $guestSwitches = null;
            $switchesSnapshot = null;

            $kid = (int)($_POST['keyboard_id'] ?? 0);
            $pickedKb = $kid > 0 ? find_keyboard($kid) : null;
            if ($pickedKb) {
                $keyboardId = $pickedKb['id'];
                // Snapshot the switches as they are RIGHT NOW. If this keyboard
                // gets upgraded to different switches later, this result still
                // correctly shows what was actually used at the time.
                $switchesSnapshot = $pickedKb['switches'] ?? null;
            } else {
                $guestModel = trim($_POST['guest_model'] ?? '');
                $guestSwitches = trim($_POST['guest_switches'] ?? '');
                $guestModel = $guestModel !== '' ? $guestModel : null;
                $guestSwitches = $guestSwitches !== '' ? $guestSwitches : null;
            }

            $stmt = get_db()->prepare(
                'INSERT INTO typing_records (wpm, accuracy, raw_wpm, duration, keyboard_id, guest_model, guest_switches, switches_snapshot)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$wpm, min($acc, 100), $raw, $dur, $keyboardId, $guestModel, $guestSwitches, $switchesSnapshot]);

            header('Location: ' . base_url('typing-test.php?saved=1'));
            exit;
        }
    }
}

$allKb = all_keyboards('name ASC');

$history = get_db()->query(
    "SELECT r.*, k.name AS kb_name, k.model AS kb_model
     FROM typing_records r
     LEFT JOIN keyboards k ON k.id = r.keyboard_id
     ORDER BY r.created_at DESC, r.id DESC
     LIMIT 15"
)->fetchAll();

$page_title = 'Typing Test — ' . SITE_TITLE;
require __DIR__ . '/includes/header.php';
?>

<div class="page-head">
  <div>
    <h1>Typing test</h1>
    <p>See what you can actually do on one of your builds — log the run against whichever keyboard you used.</p>
  </div>
</div>

<?php if ($error): ?><div class="flash"><?= h($error) ?></div><?php endif; ?>
<?php if ($saved): ?><div class="flash ok">Result saved.</div><?php endif; ?>

<style>
  .tt{ margin-top:10px; }
  .tt-opts{ display:flex; gap:4px; padding:4px; background:var(--surface); border:1px solid var(--line); border-radius:999px; width:fit-content; margin:0 auto 22px; }
  .tt-opts button{ font-family:"JetBrains Mono",monospace; font-size:.85rem; background:none; border:0; color:var(--keycap-faint); padding:6px 16px; border-radius:999px; cursor:pointer; }
  .tt-opts button.on{ color:#201202; background:var(--accent); font-weight:600; }
  #tt-live{ text-align:center; font-family:"JetBrains Mono",monospace; font-size:2.4rem; color:var(--accent); height:3rem; }
  /* height: two declarations on purpose. The plain rem value is a fallback
     for very old browsers; calc(3lh + 2rem) is the real one — `lh` means
     "the element's own line-height", taken straight from the browser's
     layout engine itself, so "3lh" can never drift out of sync with what's
     actually rendered the way a JS-measured guess could. A browser that
     doesn't understand `lh` just ignores that line and keeps the rem one. */
  #tt-words{ position:relative; width:100%; box-sizing:content-box; text-align:left; font-family:"JetBrains Mono",monospace; font-size:1.3rem; line-height:2.2rem; height:7.8rem; height:calc(3lh - 10px); padding:1rem 1.5rem; overflow:hidden; cursor:text; user-select:none; background:var(--surface); border:1px solid var(--line); border-radius:14px; }
  #tt-words:focus-within, #tt-words:hover{ border-color:var(--accent-dim); }
  #tt-words span.w{ display:inline-block; margin-right:.8ch; }
  .tt-c{ color:var(--keycap-faint); } .tt-c.ok{ color:var(--keycap); } .tt-c.bad{ color:var(--danger); } .tt-c.extra{ color:var(--danger); opacity:.65; }
  .tt-caret{ position:absolute; width:2px; height:1.5rem; background:var(--accent); animation:tt-blink 1s steps(2) infinite; }
  @keyframes tt-blink{ 50%{ opacity:0; } }
  #tt-input{ position:absolute; opacity:0; pointer-events:none; }
  #tt-hint{ text-align:center; color:var(--keycap-faint); margin-top:16px; font-size:.85rem; }
  #tt-result{ display:none; width:100%; margin:30px auto 0; }
  .tt-stats{ display:flex; gap:40px; flex-wrap:wrap; justify-content:center; margin-bottom:22px; }
  .tt-stat b{ display:block; font-size:2.6rem; font-weight:700; font-family:"JetBrains Mono",monospace; color:var(--accent); line-height:1; }
  .tt-stat small{ color:var(--keycap-faint); font-size:.8rem; }
  #tt-chart{ width:100%; height:130px; background:var(--surface); border:1px solid var(--line); border-radius:12px; }
  .save-panel{ margin-top:22px; background:var(--surface); border:1px solid var(--line); border-radius:12px; padding:20px 22px; }
  .save-panel p.title{ margin:0 0 12px; font-family:"JetBrains Mono",monospace; font-size:12px; color:var(--keycap-dim); }
  .save-panel select{ width:100%; margin-bottom:14px; }
  .guest-fields{ display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px; }
  .guest-fields input{ flex:1; min-width:200px; }
</style>

<section class="tt">
  <div class="tt-opts" id="tt-opts"></div>
  <div id="tt-live">30</div>
  <div id="tt-words"><div class="tt-caret" id="tt-caret"></div></div>
  <input id="tt-input" autocomplete="off" autocapitalize="off" spellcheck="false">
  <p id="tt-hint">Click the text and start typing. Press Tab to restart.</p>

  <div id="tt-result">
    <div class="tt-stats">
      <div class="tt-stat"><b id="tt-rWpm">0</b><small>words per minute</small></div>
      <div class="tt-stat"><b id="tt-rAcc">0%</b><small>accuracy</small></div>
      <div class="tt-stat"><b id="tt-rRaw">0</b><small>raw speed</small></div>
    </div>
    <canvas id="tt-chart"></canvas>

    <form method="post" id="tt-saveForm" class="save-panel">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save_result">
      <input type="hidden" name="wpm" value="">
      <input type="hidden" name="acc" value="">
      <input type="hidden" name="raw" value="">
      <input type="hidden" name="dur" value="">

      <p class="title">LOG THIS RUN AGAINST A KEYBOARD</p>
      <?php if ($allKb): ?>
        <select name="keyboard_id" id="tt-kbSelect">
          <option value="">— none / not sure —</option>
          <?php foreach ($allKb as $kb): ?>
            <option value="<?= $kb['id'] ?>"><?= h($kb['name']) ?><?= $kb['model'] ? ' — ' . h($kb['model']) : '' ?></option>
          <?php endforeach; ?>
        </select>
      <?php else: ?>
        <p class="hint">No keyboards in the collection yet.</p>
      <?php endif; ?>
      <div class="guest-fields" id="tt-guestFields">
        <input type="text" name="guest_model" placeholder="Keyboard model, e.g. Keychron V1" maxlength="150">
        <input type="text" name="guest_switches" placeholder="Switches, e.g. Gateron Oil Kings" maxlength="150">
      </div>

      <button class="btn btn-accent btn-sm" type="submit">Save result</button>
    </form>

    <div class="form-actions" style="justify-content:center;">
      <button class="btn" type="button" id="tt-again">Try again</button>
    </div>
  </div>
</section>

<?php if ($history): ?>
  <h2 style="margin-top:50px;font-size:13px;font-family:'JetBrains Mono',monospace;color:var(--accent);border-bottom:1px solid var(--line);padding-bottom:10px;display:flex;justify-content:space-between;align-items:center;">
    RECENT RESULTS
    <?php if (is_logged_in()): ?>
      <a href="<?= base_url('admin/typing-results.php') ?>" class="btn btn-sm" style="font-family:'Space Grotesk',sans-serif;">View all results</a>
    <?php endif; ?>
  </h2>
  <div class="compare-table-wrap">
    <table class="compare-table">
      <thead><tr><th>Date</th><th>Length</th><th>WPM</th><th>Accuracy</th><th>Keyboard</th><th>Switches</th></tr></thead>
      <tbody>
        <?php foreach ($history as $r):
          $switchesShown = $r['keyboard_id'] ? $r['switches_snapshot'] : $r['guest_switches'];
        ?>
          <tr>
            <td><?= h(date('M j, Y g:i A', strtotime($r['created_at']))) ?></td>
            <td><?= (int)$r['duration'] ?>s</td>
            <td><?= (int)$r['wpm'] ?></td>
            <td><?= (int)$r['accuracy'] ?>%</td>
            <td>
              <?php if ($r['keyboard_id'] && $r['kb_name']): ?>
                <a href="<?= base_url('keyboard.php?id=' . $r['keyboard_id']) ?>"><?= h($r['kb_name']) ?></a><?= $r['kb_model'] ? ' <span class="mono">' . h($r['kb_model']) . '</span>' : '' ?>
              <?php elseif ($r['guest_model']): ?>
                <?= h($r['guest_model']) ?> <span class="mini-tag">guest</span>
              <?php else: ?>—<?php endif; ?>
            </td>
            <td><?= $switchesShown ? h($switchesShown) : '—' ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<script>
const WORDS = "the of and to in is you that it he was for on are as with his they at be this from have or by one had not but what all were when we there can an your which their said if do will each about how up out them then she many some so these would other into has more her two like him see time could no make than first been its who now people my made over did down only way find use may water long little very after words called just where most know get through back much before go good new write our used me man too any day same right look think also around another came come work three word must because does part even place well such here take why help put different away again off went old number great tell men say small every found still between name should home big give air line set own under read last never us left end along while might next sound below saw something thought both few those always looked show large often together asked house don't world going want school important until form food keep children feet land side without boy once animals life enough took sometimes four head above kind began almost live page got earth need far hand high year mother light country father let night picture being study second soon story since white ever paper hard near sentence better best across during today however sure knew try told young sun thing whole hear example heard several change answer room sea against top turned learn point city play toward five using himself usually".split(" ");

const $ = id => document.getElementById(id);
const wordsEl=$('tt-words'), caret=$('tt-caret'), input=$('tt-input'), live=$('tt-live');
let duration = 30;
let target, wi, typed, started, startT, timer, finished, correctChars, totalTyped, samples, errors;
let lineHPx = 0;

// The box's height is owned entirely by CSS now (height: calc(3lh + 2rem) —
// "3lh" is the browser's own real computed line-height for this element,
// times 3). JS must NOT also set an inline height here: an inline style
// always wins over a stylesheet rule, so doing both silently throws away
// the CSS fix and brings back exactly the drift this was meant to kill.
// All JS needs is the same line-height number, for the scroll-snap amount —
// read straight from the computed style, the same source CSS's "lh" uses,
// so the two can never disagree.
function sizeWordsBox(){
  lineHPx = parseFloat(getComputedStyle(wordsEl).lineHeight) || 35;
}

function buildOpts(){
  $('tt-opts').innerHTML='';
  [15,30,60,120].forEach(s=>{
    const b=document.createElement('button');
    b.textContent=s+'s'; b.className=s===duration?'on':'';
    b.onclick=()=>{duration=s;buildOpts();reset()};
    $('tt-opts').appendChild(b);
  });
}

function pickWord(prev){
  let w;
  do { w = WORDS[Math.random()*WORDS.length|0]; } while (w === prev);
  return w;
}

function reset(){
  clearInterval(timer);
  target=[];
  for(let i=0;i<250;i++) target.push(pickWord(target[i-1]));
  wi=0; typed=target.map(()=>''); started=false; finished=false;
  correctChars=0; totalTyped=0; errors=0; samples=[];
  live.textContent=duration;
  $('tt-result').style.display='none';
  wordsEl.querySelectorAll('.w').forEach(n=>n.remove());
  target.forEach(w=>{
    const s=document.createElement('span'); s.className='w';
    s.innerHTML=[...w].map(ch=>`<span class="tt-c">${ch}</span>`).join('');
    wordsEl.appendChild(s);
  });
  wordsEl.scrollTop=0;
  input.value=''; input.focus();
  sizeWordsBox();
  render();
}

function render(){
  const ws=wordsEl.querySelectorAll('.w');
  ws.forEach((el,i)=>{
    if(i>wi+1 && i>40) return;
    const t=target[i], u=typed[i];
    let html='';
    for(let k=0;k<t.length;k++){
      const cls = k<u.length ? (u[k]===t[k]?'ok':'bad') : '';
      html+=`<span class="tt-c ${cls}">${t[k]}</span>`;
    }
    for(let k=t.length;k<u.length;k++) html+=`<span class="tt-c extra">${u[k]}</span>`;
    el.innerHTML=html;
  });
  placeCaret();
}

function placeCaret(){
  const el=wordsEl.querySelectorAll('.w')[wi]; if(!el) return;
  const chars=el.querySelectorAll('.tt-c');
  const u=typed[wi].length;
  let x,y;
  const base=wordsEl.getBoundingClientRect();
  if(u<chars.length){const r=chars[u].getBoundingClientRect();x=r.left;y=r.top}
  else{const r=chars[chars.length-1].getBoundingClientRect();x=r.right;y=r.top}
  // 3 lines are visible at a time; once the caret reaches the 3rd line,
  // snap up by exactly one measured line — never a fraction of one, so the
  // box always shows exactly 3 whole lines, no partial line ever visible.
  if(y-base.top>lineHPx*2){wordsEl.scrollTop+=lineHPx;return placeCaret()}
  caret.style.left=(x-base.left)+'px';
  caret.style.top=(y-base.top+wordsEl.scrollTop+4)+'px';
}

function tick(){
  const el=(Date.now()-startT)/1000;
  live.textContent=Math.max(0,Math.ceil(duration-el));
  samples.push(calc(el).wpm);
  if(el>=duration) finish();
}

function calc(sec){
  const mins=Math.max(sec,1)/60;
  return {wpm:Math.round(correctChars/5/mins), raw:Math.round(totalTyped/5/mins),
          acc:totalTyped?Math.round(100*(totalTyped-errors)/totalTyped):100};
}

input.addEventListener('keydown',e=>{
  if(e.key==='Tab'){e.preventDefault();reset();return}
  if(finished) return;
  if(e.ctrlKey||e.metaKey||e.altKey) return;
  if(e.key===' '){
    e.preventDefault();
    if(!typed[wi].length) return;
    const ok = typed[wi]===target[wi];
    if(ok) correctChars+=target[wi].length+1;
    totalTyped++; if(!ok) errors++;
    wi++; render(); return;
  }
  if(e.key==='Backspace'){
    e.preventDefault();
    typed[wi]=typed[wi].slice(0,-1); render(); return;
  }
  if(e.key.length===1){
    e.preventDefault();
    if(!started){started=true;startT=Date.now();timer=setInterval(tick,1000)}
    if(typed[wi].length>=target[wi].length+8) return;
    const k=typed[wi].length, ch=e.key;
    typed[wi]+=ch; totalTyped++;
    if(ch!==target[wi][k]) errors++;
    render();
  }
});

function finish(){
  finished=true; clearInterval(timer);
  if(typed[wi]===target[wi]) correctChars+=target[wi].length;
  const r=calc(duration);
  $('tt-rWpm').textContent=r.wpm; $('tt-rAcc').textContent=r.acc+'%'; $('tt-rRaw').textContent=r.raw;
  $('tt-result').style.display='block';
  drawChart();

  const f = $('tt-saveForm');
  if (f) {
    f.elements['wpm'].value = r.wpm;
    f.elements['acc'].value = r.acc;
    f.elements['raw'].value = r.raw;
    f.elements['dur'].value = duration;
  }
}

function drawChart(){
  const c=$('tt-chart'), dpr=window.devicePixelRatio||1;
  c.width=c.clientWidth*dpr; c.height=c.clientHeight*dpr;
  const g=c.getContext('2d'); g.scale(dpr,dpr);
  const W=c.clientWidth,H=c.clientHeight,p=20;
  const max=Math.max(10,...samples)*1.1, n=samples.length;
  g.strokeStyle=getComputedStyle(document.documentElement).getPropertyValue('--accent');
  g.lineWidth=2; g.beginPath();
  samples.forEach((v,i)=>{
    const x=p+(n>1?i/(n-1):0)*(W-2*p), y=H-p-(v/max)*(H-2*p);
    i?g.lineTo(x,y):g.moveTo(x,y);
  });
  g.stroke();
}

// show the guest text fields only when "none / not sure" is selected
const kbSelect = $('tt-kbSelect'), guestFields = $('tt-guestFields');
function syncGuestFields(){
  if (!kbSelect || !guestFields) return;
  guestFields.style.display = kbSelect.value === '' ? 'flex' : 'none';
}
if (kbSelect) { kbSelect.addEventListener('change', syncGuestFields); syncGuestFields(); }
else if (guestFields) { guestFields.style.display = 'flex'; }

wordsEl.addEventListener('click',()=>input.focus());
document.addEventListener('keydown',e=>{ if(!finished && document.activeElement!==input) input.focus(); });
$('tt-again').onclick=reset;
window.addEventListener('resize',()=>{sizeWordsBox(); if(!finished)placeCaret();});

// Google Fonts loads with font-display:swap, so JetBrains Mono isn't
// necessarily ready the instant this script runs — it can still be showing
// a fallback system font for a moment, then swap in with different metrics
// mid-session. Waiting for document.fonts.ready means the very first
// measurement is already using the real font, so nothing shifts later.
function boot(){ buildOpts(); reset(); }
if (document.fonts && document.fonts.ready) {
  document.fonts.ready.then(boot);
} else {
  boot();
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
