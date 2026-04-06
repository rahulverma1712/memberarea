<?php
/**
 * Plugin Name: Claude AI Virtual Assistant
 * Plugin URI:  https://your-site.com
 * Description: A virtual AI assistant powered by Claude, embedded inside your WordPress dashboard.
 * Version:     2.0.0
 * Author:      Your Name
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Settings ──────────────────────────────────────────────────────────────────
add_action( 'admin_menu', function () {
    add_menu_page( 'Claude Assistant', 'Claude Assistant', 'manage_options', 'claude-assistant', 'claude_va_render_page', 'dashicons-superhero', 3 );
    add_submenu_page( 'claude-assistant', 'Settings', 'Settings', 'manage_options', 'claude-assistant-settings', 'claude_va_render_settings' );
} );

add_action( 'admin_init', function () {
    register_setting( 'claude_va_settings', 'claude_va_api_key', [ 'sanitize_callback' => 'sanitize_text_field' ] );
} );

function claude_va_render_settings() { ?>
<div class="wrap">
    <h1>Claude Assistant — Settings</h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'claude_va_settings' ); ?>
        <table class="form-table">
            <tr>
                <th><label for="claude_va_api_key">Anthropic API Key</label></th>
                <td>
                    <input type="password" id="claude_va_api_key" name="claude_va_api_key"
                           value="<?php echo esc_attr( get_option('claude_va_api_key') ); ?>" class="regular-text" />
                    <p class="description">Get your key from <a href="https://console.anthropic.com" target="_blank">console.anthropic.com</a></p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
<?php }

// ── Main Page ─────────────────────────────────────────────────────────────────
function claude_va_render_page() {
    $api_key   = get_option( 'claude_va_api_key', '' );
    $nonce     = wp_create_nonce( 'claude_va_nonce' );
    $ajax_url  = admin_url( 'admin-ajax.php' );
    $site_name = get_bloginfo( 'name' );
    $site_url  = get_site_url();
    $settings_url = admin_url( 'admin.php?page=claude-assistant-settings' );
    ?>
    <style>
    /* ── Reset & Vars ── */
    #cva-app *{box-sizing:border-box;margin:0;padding:0}
    #cva-app{
      --bg:#0d0d14;--surface:#13131f;--surface2:#1a1a2e;--surface3:#21213a;
      --border:#2a2a45;--purple:#7c3aed;--purple-light:#a78bfa;--purple-dim:rgba(124,58,237,.15);
      --blue:#3b82f6;--green:#10b981;--red:#ef4444;--yellow:#f59e0b;
      --text:#f1f5f9;--text-muted:#64748b;--text-dim:#94a3b8;
      font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
      background:var(--bg);border-radius:16px;overflow:hidden;
      height:calc(100vh - 72px);display:flex;flex-direction:column;
      margin:12px;box-shadow:0 24px 80px rgba(0,0,0,.6);
      border:1px solid var(--border);
    }

    /* ── Top Bar ── */
    #cva-topbar{
      display:flex;align-items:center;gap:12px;
      padding:0 20px;height:56px;flex-shrink:0;
      background:var(--surface);border-bottom:1px solid var(--border);
      position:relative;
    }
    #cva-topbar::after{
      content:'';position:absolute;bottom:0;left:0;right:0;height:1px;
      background:linear-gradient(90deg,transparent,var(--purple),transparent);
    }
    .cva-logo{display:flex;align-items:center;gap:10px}
    .cva-logo-icon{
      width:32px;height:32px;border-radius:8px;
      background:linear-gradient(135deg,var(--purple),#4f46e5);
      display:flex;align-items:center;justify-content:center;
      font-size:16px;box-shadow:0 0 14px rgba(124,58,237,.5);
    }
    .cva-logo-text{font-size:15px;font-weight:700;color:var(--text)}
    .cva-logo-text span{color:var(--purple-light)}
    .cva-site-pill{
      margin-left:4px;background:var(--purple-dim);border:1px solid rgba(124,58,237,.3);
      color:var(--purple-light);font-size:11px;padding:3px 10px;border-radius:20px;
    }
    .cva-topbar-right{margin-left:auto;display:flex;align-items:center;gap:8px}
    .cva-status{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted)}
    .cva-status-dot{width:7px;height:7px;border-radius:50%;background:var(--green);box-shadow:0 0 6px var(--green);animation:statusPulse 2s infinite}
    @keyframes statusPulse{0%,100%{opacity:1}50%{opacity:.4}}
    .cva-icon-btn{
      width:32px;height:32px;border-radius:8px;border:1px solid var(--border);
      background:transparent;color:var(--text-muted);cursor:pointer;
      display:flex;align-items:center;justify-content:center;font-size:14px;
      transition:all .2s;
    }
    .cva-icon-btn:hover{background:var(--surface3);color:var(--text);border-color:var(--purple)}

    /* ── Body Layout ── */
    #cva-body{display:flex;flex:1;overflow:hidden}

    /* ── Sidebar ── */
    #cva-sidebar{
      width:220px;flex-shrink:0;background:var(--surface);
      border-right:1px solid var(--border);display:flex;flex-direction:column;
      padding:16px 12px;gap:6px;overflow-y:auto;
    }
    .cva-section-label{
      font-size:10px;font-weight:700;color:var(--text-muted);
      text-transform:uppercase;letter-spacing:.8px;padding:8px 8px 4px;
    }
    .cva-quick-btn{
      display:flex;align-items:center;gap:10px;width:100%;
      background:transparent;border:none;color:var(--text-dim);
      font-size:12.5px;padding:9px 10px;border-radius:8px;cursor:pointer;
      text-align:left;transition:all .2s;border:1px solid transparent;
    }
    .cva-quick-btn:hover{background:var(--surface3);color:var(--text);border-color:var(--border)}
    .cva-quick-btn.active{background:var(--purple-dim);color:var(--purple-light);border-color:rgba(124,58,237,.3)}
    .cva-quick-icon{font-size:14px;width:20px;text-align:center}
    .cva-sidebar-divider{height:1px;background:var(--border);margin:6px 4px}

    /* ── Chat Area ── */
    #cva-main{flex:1;display:flex;flex-direction:column;overflow:hidden}

    #cva-messages{
      flex:1;overflow-y:auto;padding:24px 28px;
      display:flex;flex-direction:column;gap:20px;
      background:var(--bg);
    }
    #cva-messages::-webkit-scrollbar{width:4px}
    #cva-messages::-webkit-scrollbar-track{background:transparent}
    #cva-messages::-webkit-scrollbar-thumb{background:var(--border);border-radius:4px}

    /* ── Message Rows ── */
    .cva-row{display:flex;gap:10px;align-items:flex-start;animation:msgIn .3s ease}
    @keyframes msgIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .cva-row.user{flex-direction:row-reverse}

    .cva-avatar{
      width:32px;height:32px;border-radius:10px;flex-shrink:0;
      display:flex;align-items:center;justify-content:center;font-size:15px;
    }
    .cva-avatar.ai{background:linear-gradient(135deg,var(--purple),#4f46e5);box-shadow:0 0 12px rgba(124,58,237,.4)}
    .cva-avatar.user{background:var(--surface3);border:1px solid var(--border)}
    .cva-avatar.tool{background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3)}

    .cva-bubble{
      max-width:72%;border-radius:14px;padding:12px 16px;
      font-size:13.5px;line-height:1.65;
    }
    .cva-row.ai .cva-bubble{
      background:var(--surface2);border:1px solid var(--border);
      color:var(--text);border-top-left-radius:4px;
    }
    .cva-row.user .cva-bubble{
      background:linear-gradient(135deg,var(--purple),#4f46e5);
      color:#fff;border-top-right-radius:4px;
      box-shadow:0 4px 20px rgba(124,58,237,.35);
    }
    .cva-row.tool .cva-bubble{
      background:rgba(16,185,129,.07);border:1px solid rgba(16,185,129,.2);
      color:var(--text-dim);border-top-left-radius:4px;max-width:85%;font-size:12.5px;
    }
    .cva-tool-header{
      display:flex;align-items:center;gap:8px;
      color:#10b981;font-size:11px;font-weight:700;
      text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;
    }
    .cva-tool-badge{
      background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);
      color:#10b981;padding:1px 8px;border-radius:10px;font-size:10px;
    }

    /* table */
    .cva-table{width:100%;border-collapse:collapse;font-size:12px;margin-top:6px}
    .cva-table th{background:rgba(16,185,129,.1);padding:6px 10px;text-align:left;color:#10b981;font-weight:600;border-bottom:1px solid rgba(16,185,129,.2)}
    .cva-table td{padding:6px 10px;border-bottom:1px solid rgba(255,255,255,.04);color:var(--text-dim)}
    .cva-table tr:last-child td{border:none}
    .cva-table tr:hover td{background:rgba(255,255,255,.02)}

    /* kv rows */
    .cva-kv{display:grid;grid-template-columns:auto 1fr;gap:4px 12px;font-size:12px}
    .cva-kv-k{color:var(--text-muted);font-weight:600;white-space:nowrap}
    .cva-kv-v{color:var(--text-dim)}

    /* welcome */
    #cva-welcome{
      display:flex;flex-direction:column;align-items:center;justify-content:center;
      flex:1;gap:28px;padding:32px;text-align:center;
    }
    .cva-welcome-icon{
      width:64px;height:64px;border-radius:18px;
      background:linear-gradient(135deg,var(--purple),#4f46e5);
      display:flex;align-items:center;justify-content:center;font-size:30px;
      box-shadow:0 0 40px rgba(124,58,237,.4);
    }
    .cva-welcome-title{font-size:22px;font-weight:800;color:var(--text)}
    .cva-welcome-sub{font-size:13px;color:var(--text-muted);max-width:380px;line-height:1.7}
    .cva-chips{display:flex;flex-wrap:wrap;gap:8px;justify-content:center;max-width:480px}
    .cva-chip{
      background:var(--surface2);border:1px solid var(--border);color:var(--text-dim);
      font-size:12px;padding:7px 14px;border-radius:20px;cursor:pointer;transition:all .2s;
    }
    .cva-chip:hover{background:var(--purple-dim);color:var(--purple-light);border-color:rgba(124,58,237,.4)}

    /* loading */
    .cva-typing{display:flex;align-items:center;gap:5px;padding:4px 0}
    .cva-typing span{
      width:7px;height:7px;border-radius:50%;background:var(--purple-light);
      animation:typing 1.2s infinite ease-in-out;
    }
    .cva-typing span:nth-child(2){animation-delay:.2s}
    .cva-typing span:nth-child(3){animation-delay:.4s}
    @keyframes typing{0%,80%,100%{transform:scale(0);opacity:.4}40%{transform:scale(1);opacity:1}}

    /* ── Input Bar ── */
    #cva-inputbar{
      padding:16px 20px;background:var(--surface);
      border-top:1px solid var(--border);flex-shrink:0;
    }
    .cva-input-wrap{
      display:flex;align-items:flex-end;gap:10px;
      background:var(--surface2);border:1px solid var(--border);
      border-radius:14px;padding:10px 10px 10px 16px;
      transition:border-color .2s;
    }
    .cva-input-wrap:focus-within{border-color:var(--purple);box-shadow:0 0 0 3px rgba(124,58,237,.12)}
    #cva-input{
      flex:1;background:transparent;border:none;outline:none;
      color:var(--text);font-size:13.5px;resize:none;
      font-family:inherit;line-height:1.6;max-height:120px;
      scrollbar-width:none;
    }
    #cva-input::placeholder{color:var(--text-muted)}
    #cva-input::-webkit-scrollbar{display:none}
    #cva-send{
      width:38px;height:38px;border-radius:10px;border:none;cursor:pointer;
      background:linear-gradient(135deg,var(--purple),#4f46e5);color:#fff;
      display:flex;align-items:center;justify-content:center;font-size:16px;
      flex-shrink:0;transition:all .2s;box-shadow:0 4px 14px rgba(124,58,237,.4);
    }
    #cva-send:hover{transform:scale(1.06);box-shadow:0 6px 20px rgba(124,58,237,.55)}
    #cva-send:disabled{background:var(--surface3);box-shadow:none;transform:none;cursor:not-allowed}
    .cva-input-hint{font-size:11px;color:var(--text-muted);margin-top:7px;text-align:center}

    /* ── No Key Banner ── */
    #cva-no-key{
      background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);
      color:#f59e0b;font-size:12.5px;padding:10px 20px;text-align:center;flex-shrink:0;
    }
    #cva-no-key a{color:#fbbf24;font-weight:600}
    </style>

    <div id="cva-app">

      <!-- Top Bar -->
      <div id="cva-topbar">
        <div class="cva-logo">
          <div class="cva-logo-icon">⚡</div>
          <div class="cva-logo-text">Claude <span>Assistant</span></div>
        </div>
        <div class="cva-site-pill"><?php echo esc_html( $site_name ); ?></div>
        <div class="cva-topbar-right">
          <div class="cva-status">
            <div class="cva-status-dot"></div>
            <?php echo $api_key ? 'Connected' : 'No API Key'; ?>
          </div>
          <button class="cva-icon-btn" id="cva-clear-btn" title="Clear chat">🗑</button>
        </div>
      </div>

      <!-- Body -->
      <div id="cva-body">

        <!-- Sidebar -->
        <div id="cva-sidebar">
          <div class="cva-section-label">Quick Actions</div>
          <button class="cva-quick-btn" data-q="Show me all site information">
            <span class="cva-quick-icon">📊</span> Site Overview
          </button>
          <button class="cva-quick-btn" data-q="List my last 10 posts">
            <span class="cva-quick-icon">📝</span> Recent Posts
          </button>
          <button class="cva-quick-btn" data-q="List all pages">
            <span class="cva-quick-icon">📄</span> Pages
          </button>
          <button class="cva-quick-btn" data-q="List all users">
            <span class="cva-quick-icon">👥</span> Users
          </button>
          <div class="cva-sidebar-divider"></div>
          <div class="cva-section-label">Content</div>
          <button class="cva-quick-btn" data-q="Show pending comments">
            <span class="cva-quick-icon">💬</span> Comments
          </button>
          <button class="cva-quick-btn" data-q="List recent media uploads">
            <span class="cva-quick-icon">🖼</span> Media
          </button>
          <button class="cva-quick-btn" data-q="List all categories">
            <span class="cva-quick-icon">🏷</span> Categories
          </button>
          <div class="cva-sidebar-divider"></div>
          <div class="cva-section-label">System</div>
          <button class="cva-quick-btn" data-q="List all plugins and tell me which are active">
            <span class="cva-quick-icon">🔌</span> Plugins
          </button>
          <button class="cva-quick-btn" data-q="What are my current site settings?">
            <span class="cva-quick-icon">⚙️</span> Settings
          </button>
        </div>

        <!-- Chat -->
        <div id="cva-main">
          <?php if ( ! $api_key ) : ?>
          <div id="cva-no-key">⚠ No API key configured. <a href="<?php echo esc_url($settings_url); ?>">Add your Anthropic API key →</a></div>
          <?php endif; ?>

          <div id="cva-messages">
            <div id="cva-welcome">
              <div class="cva-welcome-icon">⚡</div>
              <div>
                <div class="cva-welcome-title">Hello! I'm your WordPress Assistant</div>
                <div class="cva-welcome-sub" style="margin-top:8px">I can manage posts, pages, users, plugins, comments, settings and more — all from a single conversation.</div>
              </div>
              <div class="cva-chips">
                <div class="cva-chip" data-q="Show me site overview">📊 Site Overview</div>
                <div class="cva-chip" data-q="Create a new draft post">✍️ Create a Post</div>
                <div class="cva-chip" data-q="List all plugins and tell me which are active">🔌 Check Plugins</div>
                <div class="cva-chip" data-q="Show pending comments">💬 Pending Comments</div>
                <div class="cva-chip" data-q="List all users and their roles">👥 Manage Users</div>
                <div class="cva-chip" data-q="List all categories">🏷 Categories</div>
              </div>
            </div>
          </div>

          <div id="cva-inputbar">
            <div class="cva-input-wrap">
              <textarea id="cva-input" placeholder="Ask me anything about your WordPress site…" rows="1"></textarea>
              <button id="cva-send">➤</button>
            </div>
            <div class="cva-input-hint">Press Enter to send · Shift+Enter for new line</div>
          </div>
        </div>

      </div>
    </div>

    <script>
    (function(){
      const API_KEY  = <?php echo json_encode( $api_key ); ?>;
      const NONCE    = <?php echo json_encode( $nonce ); ?>;
      const AJAX_URL = <?php echo json_encode( $ajax_url ); ?>;
      const SITE     = <?php echo json_encode( $site_name ); ?>;
      const MODEL    = 'claude-sonnet-4-20250514';

      const SYSTEM = `You are a powerful WordPress Virtual Assistant for "${SITE}". Call tools by responding ONLY with JSON: {"tool":"<name>","params":{...}}

TOOLS:
list_posts {count?,status?,type?} | create_post {title,content,status?,type?} | update_post {id,title?,content?,status?} | delete_post {id,force?} | get_post {id} | list_pages {count?} | list_users {count?} | create_user {username,email,role?} | list_plugins {} | activate_plugin {plugin} | deactivate_plugin {plugin} | get_site_info {} | update_option {key,value} | list_media {count?} | list_comments {count?,status?} | approve_comment {id} | delete_comment {id} | list_categories {} | create_category {name}

After tool results, respond naturally. One tool per turn. Be concise and friendly.`;

      let history = [], loading = false;
      const msgBox  = document.getElementById('cva-messages');
      const input   = document.getElementById('cva-input');
      const sendBtn = document.getElementById('cva-send');
      const welcome = document.getElementById('cva-welcome');

      // auto-resize textarea
      input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
      });

      function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

      function addRow(type, html) {
        if(welcome) welcome.style.display = 'none';
        const avatars = { ai:'⚡', user:'👤', tool:'🔧' };
        const div = document.createElement('div');
        div.className = `cva-row ${type}`;
        div.innerHTML = `<div class="cva-avatar ${type}">${avatars[type]||'?'}</div><div class="cva-bubble">${html}</div>`;
        msgBox.appendChild(div);
        msgBox.scrollTop = msgBox.scrollHeight;
        return div;
      }

      function formatResult(data) {
        if(Array.isArray(data)){
          if(!data.length) return '<em style="color:var(--text-muted)">No results found.</em>';
          const keys = Object.keys(data[0]);
          let h = `<table class="cva-table"><thead><tr>${keys.map(k=>`<th>${esc(k)}</th>`).join('')}</tr></thead><tbody>`;
          data.forEach(r => { h += `<tr>${keys.map(k=>`<td>${esc(r[k]??'')}</td>`).join('')}</tr>`; });
          return h + '</tbody></table>';
        }
        if(typeof data === 'object'){
          return `<div class="cva-kv">${Object.entries(data).map(([k,v])=>`<div class="cva-kv-k">${esc(k)}</div><div class="cva-kv-v">${esc(v)}</div>`).join('')}</div>`;
        }
        return esc(String(data));
      }

      async function wpAction(action, params) {
        const fd = new FormData();
        fd.append('action','claude_va_execute');
        fd.append('nonce', NONCE);
        fd.append('action_type', action);
        fd.append('params', JSON.stringify(params));
        const r = await fetch(AJAX_URL, { method:'POST', body:fd });
        const j = await r.json();
        if(!j.success) throw new Error(j.data||'WP error');
        return j.data;
      }

      async function callClaude(msgs) {
        const r = await fetch('https://api.anthropic.com/v1/messages', {
          method:'POST',
          headers:{
            'Content-Type':'application/json',
            'x-api-key': API_KEY,
            'anthropic-version':'2023-06-01',
            'anthropic-dangerous-direct-browser-calls':'true'
          },
          body: JSON.stringify({ model:MODEL, max_tokens:2048, system:SYSTEM, messages:msgs })
        });
        if(!r.ok){ const e=await r.json(); throw new Error(e.error?.message||'Claude API error'); }
        const d = await r.json();
        return d.content[0]?.text||'';
      }

      async function handleSend() {
        const text = input.value.trim();
        if(!text || loading || !API_KEY) return;
        loading = true; sendBtn.disabled = true;
        input.value = ''; input.style.height = 'auto';

        addRow('user', esc(text).replace(/\n/g,'<br>'));
        history.push({ role:'user', content:text });

        // sidebar active state
        document.querySelectorAll('.cva-quick-btn').forEach(b=>b.classList.remove('active'));

        const loadRow = addRow('ai', '<div class="cva-typing"><span></span><span></span><span></span></div>');

        try {
          let reply = await callClaude(history);
          let iter = 0;
          while(iter++ < 5) {
            let tool = null;
            try { const p = JSON.parse(reply.trim()); if(p.tool) tool = p; } catch(_){}
            if(!tool) break;

            loadRow.remove();
            let resultData, errMsg;
            try { resultData = await wpAction(tool.tool, tool.params||{}); }
            catch(e){ errMsg = e.message; }

            const resultHtml = `<div class="cva-tool-header">🔧 <span>${esc(tool.tool)}</span><span class="cva-tool-badge">${errMsg?'Error':'Success'}</span></div>`
              + (errMsg ? `<span style="color:var(--red)">${esc(errMsg)}</span>` : formatResult(resultData));
            addRow('tool', resultHtml);

            history.push({ role:'assistant', content:reply });
            history.push({ role:'user', content:`Tool result for "${tool.tool}":\n${errMsg||JSON.stringify(resultData,null,2)}` });

            const nextLoad = addRow('ai','<div class="cva-typing"><span></span><span></span><span></span></div>');
            reply = await callClaude(history);
            nextLoad.remove();
          }

          loadRow.remove?.();
          addRow('ai', esc(reply).replace(/\n/g,'<br>'));
          history.push({ role:'assistant', content:reply });

        } catch(e) {
          loadRow.remove();
          addRow('ai', `<span style="color:var(--red)">⚠ ${esc(e.message)}</span>`);
        }

        loading = false; sendBtn.disabled = false; input.focus();
      }

      sendBtn.addEventListener('click', handleSend);
      input.addEventListener('keydown', e => { if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();handleSend();} });
      document.getElementById('cva-clear-btn').addEventListener('click', () => {
        history = [];
        msgBox.innerHTML = '';
        const w = document.createElement('div'); w.id='cva-welcome';
        w.innerHTML = document.getElementById('cva-welcome')?.innerHTML || '';
        msgBox.appendChild(w);
        location.reload();
      });

      // Quick action buttons
      document.querySelectorAll('.cva-quick-btn, .cva-chip').forEach(btn => {
        btn.addEventListener('click', () => {
          document.querySelectorAll('.cva-quick-btn').forEach(b=>b.classList.remove('active'));
          btn.classList.add('active');
          input.value = btn.dataset.q;
          handleSend();
        });
      });
    })();
    </script>
    <?php
}

// ── AJAX Handler ──────────────────────────────────────────────────────────────
add_action( 'wp_ajax_claude_va_execute', function () {
    check_ajax_referer( 'claude_va_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized', 403 );
    $action = sanitize_text_field( $_POST['action_type'] ?? '' );
    $params = json_decode( stripslashes( $_POST['params'] ?? '{}' ), true );
    wp_send_json_success( claude_va_dispatch( $action, $params ) );
} );

// ── Dispatcher ────────────────────────────────────────────────────────────────
function claude_va_dispatch( $action, $params ) {
    switch ( $action ) {
        case 'list_posts':
            $posts = get_posts([ 'numberposts'=>intval($params['count']??10), 'post_status'=>sanitize_text_field($params['status']??'any'), 'post_type'=>sanitize_text_field($params['type']??'post') ]);
            return array_map(fn($p)=>['id'=>$p->ID,'title'=>$p->post_title,'status'=>$p->post_status,'date'=>$p->post_date], $posts);
        case 'create_post':
            $id = wp_insert_post(['post_title'=>sanitize_text_field($params['title']??'Untitled'),'post_content'=>wp_kses_post($params['content']??''),'post_status'=>sanitize_text_field($params['status']??'draft'),'post_type'=>sanitize_text_field($params['type']??'post')],true);
            return is_wp_error($id)?['error'=>$id->get_error_message()]:['id'=>$id,'message'=>"Post created (ID: $id)",'edit_url'=>get_edit_post_link($id,'raw')];
        case 'update_post':
            $data=['ID'=>intval($params['id'])];
            if(isset($params['title']))   $data['post_title']  =sanitize_text_field($params['title']);
            if(isset($params['content'])) $data['post_content']=wp_kses_post($params['content']);
            if(isset($params['status']))  $data['post_status'] =sanitize_text_field($params['status']);
            $r=wp_update_post($data,true);
            return is_wp_error($r)?['error'=>$r->get_error_message()]:['message'=>"Post updated."];
        case 'delete_post':
            $r=wp_delete_post(intval($params['id']),(bool)($params['force']??false));
            return $r?['message'=>'Post deleted.']:['error'=>'Could not delete.'];
        case 'get_post':
            $p=get_post(intval($params['id']));
            return $p?['id'=>$p->ID,'title'=>$p->post_title,'status'=>$p->post_status,'date'=>$p->post_date,'author'=>get_the_author_meta('display_name',$p->post_author)]:['error'=>'Not found.'];
        case 'list_pages':
            $pages=get_pages(['number'=>intval($params['count']??20)]);
            return array_map(fn($p)=>['id'=>$p->ID,'title'=>$p->post_title,'status'=>$p->post_status], $pages);
        case 'list_users':
            $users=get_users(['number'=>intval($params['count']??20)]);
            return array_map(fn($u)=>['id'=>$u->ID,'name'=>$u->display_name,'email'=>$u->user_email,'role'=>implode(', ',$u->roles)], $users);
        case 'create_user':
            $id=wp_create_user(sanitize_user($params['username']??''),wp_generate_password(),sanitize_email($params['email']??''));
            if(is_wp_error($id)) return ['error'=>$id->get_error_message()];
            if(isset($params['role'])){ $u=new WP_User($id); $u->set_role(sanitize_text_field($params['role'])); }
            return ['id'=>$id,'message'=>"User created (ID: $id)."];
        case 'list_plugins':
            if(!function_exists('get_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
            $active=get_option('active_plugins',[]);
            return array_map(fn($f,$d)=>['name'=>$d['Name'],'version'=>$d['Version'],'active'=>in_array($f,$active)?'Yes':'No','file'=>$f], array_keys(get_plugins()), get_plugins());
        case 'activate_plugin':
            if(!function_exists('activate_plugin')) require_once ABSPATH.'wp-admin/includes/plugin.php';
            $r=activate_plugin(sanitize_text_field($params['plugin']));
            return is_wp_error($r)?['error'=>$r->get_error_message()]:['message'=>'Plugin activated.'];
        case 'deactivate_plugin':
            if(!function_exists('deactivate_plugins')) require_once ABSPATH.'wp-admin/includes/plugin.php';
            deactivate_plugins(sanitize_text_field($params['plugin']));
            return ['message'=>'Plugin deactivated.'];
        case 'get_site_info':
            return ['name'=>get_bloginfo('name'),'description'=>get_bloginfo('description'),'url'=>get_site_url(),'admin_email'=>get_option('admin_email'),'wp_version'=>get_bloginfo('version'),'language'=>get_bloginfo('language'),'timezone'=>get_option('timezone_string'),'posts'=>wp_count_posts()->publish,'pages'=>wp_count_posts('page')->publish,'users'=>count_users()['total_users']];
        case 'update_option':
            $allowed=['blogname','blogdescription','admin_email','timezone_string','date_format','time_format'];
            $key=sanitize_key($params['key']??'');
            if(!in_array($key,$allowed)) return ['error'=>"Option '$key' not allowed."];
            update_option($key,sanitize_text_field($params['value']??''));
            return ['message'=>"Option '$key' updated."];
        case 'list_media':
            $media=get_posts(['post_type'=>'attachment','post_status'=>'inherit','numberposts'=>intval($params['count']??10)]);
            return array_map(fn($m)=>['id'=>$m->ID,'title'=>$m->post_title,'type'=>$m->post_mime_type,'url'=>wp_get_attachment_url($m->ID)], $media);
        case 'list_comments':
            $comments=get_comments(['number'=>intval($params['count']??10),'status'=>sanitize_text_field($params['status']??'hold')]);
            return array_map(fn($c)=>['id'=>$c->comment_ID,'author'=>$c->comment_author,'content'=>substr($c->comment_content,0,80).'...','status'=>$c->comment_approved], $comments);
        case 'approve_comment':
            wp_set_comment_status(intval($params['id']),'approve');
            return ['message'=>'Comment approved.'];
        case 'delete_comment':
            wp_delete_comment(intval($params['id']),true);
            return ['message'=>'Comment deleted.'];
        case 'list_categories':
            return array_map(fn($c)=>['id'=>$c->term_id,'name'=>$c->name,'slug'=>$c->slug,'count'=>$c->count], get_categories(['hide_empty'=>false]));
        case 'create_category':
            $r=wp_insert_term(sanitize_text_field($params['name']),'category');
            return is_wp_error($r)?['error'=>$r->get_error_message()]:['id'=>$r['term_id'],'message'=>'Category created.'];
        default:
            return ['error'=>"Unknown action: $action"];
    }
}