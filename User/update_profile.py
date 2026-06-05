import re

with open(r'c:\Users\HP\Desktop\MLMP\User\profile.php', 'r', encoding='utf-8') as f:
    content = f.read()

content = content.replace(
'''<div class="dash-panel" style="margin-bottom:24px;">
  <div class="dash-panel-header">
    <div class="panel-title">
      <span class="panel-icon"><i class="fa-solid fa-circle-info"></i></span>
      Profile Instructions
    </div>
  </div>
  <div class="dash-panel-body" style="padding:18px;">
    <div style="font-size:13.5px; color:#102a43; line-height:1.4;">
      <strong style="color:#102a43;">Important Instructions:</strong> All fields are mandatory. Please submit your bank details accurately. Incorrect details may lead to payment rejection.
    </div>
  </div>
</div>''',
'''<div class="bg-indigo-600/10 border border-indigo-500/20 rounded-xl p-4 mb-6 flex gap-3 items-start">
  <i class="fa-solid fa-circle-info text-indigo-400 mt-0.5"></i>
  <div class="text-sm text-indigo-200">
    <strong class="text-white">Important Instructions:</strong> All fields are mandatory. Please submit your bank details accurately. Incorrect details may lead to payment rejection.
  </div>
</div>'''
)

content = content.replace('<div class="dash-row col-2">', '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">')

content = content.replace(
'''    <div style="display:flex; flex-direction:column; gap:20px;">
      
      <!-- General Settings Panel -->
      <div class="dash-panel">
        <div class="dash-panel-header">
          <div class="panel-title">
            <span class="panel-icon"><i class="fa-solid fa-user-gear"></i></span>
            General Settings
          </div>
        </div>
        <div class="dash-panel-body" style="display:flex; flex-direction:column; gap:16px;">''',
'''    <div class="flex flex-col gap-6">
      
      <!-- General Settings Panel -->
      <div class="bg-[#16181f]/80 backdrop-blur-xl border border-white/5 rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center gap-3 bg-white/5">
          <div class="w-8 h-8 rounded-lg bg-indigo-500/20 text-indigo-400 flex items-center justify-center"><i class="fa-solid fa-user-gear"></i></div>
          <h3 class="text-sm font-bold text-slate-100">General Settings</h3>
        </div>
        <div class="p-6 flex flex-col gap-4">'''
)

content = content.replace(
'''      <!-- Security Settings Panel -->
      <div class="dash-panel">
        <div class="dash-panel-header">
          <div class="panel-title">
            <span class="panel-icon"><i class="fa-solid fa-shield-halved"></i></span>
            Security &amp; Password
          </div>
        </div>
        <div class="dash-panel-body" style="display:flex; flex-direction:column; gap:16px;">''',
'''      <!-- Security Settings Panel -->
      <div class="bg-[#16181f]/80 backdrop-blur-xl border border-white/5 rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center gap-3 bg-white/5">
          <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center"><i class="fa-solid fa-shield-halved"></i></div>
          <h3 class="text-sm font-bold text-slate-100">Security &amp; Password</h3>
        </div>
        <div class="p-6 flex flex-col gap-4">'''
)

content = content.replace(
'''    <!-- Right Column: Banking & Payouts -->
    <div style="display:flex; flex-direction:column; gap:20px;">
      
      <!-- Banking Panel -->
      <div class="dash-panel">
        <div class="dash-panel-header">
          <div class="panel-title">
            <span class="panel-icon"><i class="fa-solid fa-wallet"></i></span>
            Payout &amp; Banking Details
          </div>
        </div>
        <div class="dash-panel-body" style="display:flex; flex-direction:column; gap:16px;">''',
'''    <!-- Right Column: Banking & Payouts -->
    <div class="flex flex-col gap-6">
      
      <!-- Banking Panel -->
      <div class="bg-[#16181f]/80 backdrop-blur-xl border border-white/5 rounded-2xl shadow-xl flex flex-col overflow-hidden">
        <div class="px-5 py-4 border-b border-white/5 flex items-center gap-3 bg-white/5">
          <div class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center"><i class="fa-solid fa-wallet"></i></div>
          <h3 class="text-sm font-bold text-slate-100">Payout &amp; Banking Details</h3>
        </div>
        <div class="p-6 flex flex-col gap-4">'''
)

content = re.sub(
    r'<label style="display:block;font-size:12px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0\.7px;margin-bottom:6px;">',
    r'<label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1.5">',
    content
)

content = re.sub(
    r'''style="width:100%;background:rgba\(0,0,0,0\.02\);border:1px solid rgba\(0,0,0,0\.08\);border-radius:10px;color:#102a43;padding:11px 14px;font-size:13px;font-family:'Inter',sans-serif;outline:none;box-sizing:border-box;"\s+onfocus="this\.style\.borderColor='#7c3aed'" onblur="this\.style\.borderColor='rgba\(255,255,255,0\.1\)'"''',
    r'''class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-slate-200 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"''',
    content
)

content = re.sub(
    r'''style="width:100%;background:#eef5fb;border:1px solid #c4d8e7;border-radius:10px;color:#102a43;padding:11px 14px;font-size:13px;font-family:'Inter',sans-serif;outline:none;box-sizing:border-box;height:45px;"\s+onfocus="this\.style\.borderColor='#7c3aed'" onblur="this\.style\.borderColor='rgba\(255,255,255,0\.1\)'"''',
    r'''class="w-full bg-white/5 border border-white/10 rounded-lg px-4 py-2.5 text-slate-200 text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all h-[45px]"''',
    content
)

content = re.sub(
    r'''style="width:100%;background:rgba\(255,255,255,0\.02\);border:1px solid rgba\(255,255,255,0\.06\);border-radius:10px;color:#64748b;padding:11px 14px;font-size:13px;font-family:'Inter',sans-serif;outline:none;box-sizing:border-box;"''',
    r'''class="w-full bg-black/20 border border-white/5 rounded-lg px-4 py-2.5 text-slate-400 text-sm cursor-not-allowed"''',
    content
)

content = content.replace(
    '''style="flex:1;background:rgba(0,0,0,0.03);border:1px solid rgba(0,0,0,0.08);border-right:none;border-radius:10px 0 0 10px;color:#102a43;padding:11px 14px;font-size:13px;font-family:'Inter',sans-serif;outline:none;"''',
    '''class="flex-1 bg-white/5 border border-white/10 border-r-0 rounded-l-lg px-4 py-2.5 text-slate-200 text-sm focus:outline-none"'''
)

content = content.replace(
    '''style="background:linear-gradient(135deg,#7c3aed,#5b21b6);color:white;border:none;border-radius:0 10px 10px 0;padding:11px 18px;font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:opacity 0.2s;"\n                      onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'"''',
    '''class="bg-indigo-600 hover:bg-indigo-500 text-white rounded-r-lg px-5 py-2.5 text-xs font-semibold transition-colors"'''
)

content = content.replace(
    '''<div style="display:flex;gap:12px;flex-wrap:wrap;">''',
    '''<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-2">'''
)
content = content.replace(
    '''<div style="flex:1;min-width:180px;">''',
    '''<div>'''
)

content = content.replace(
    '''<div style="font-size:12px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:0.8px;margin-top:10px;margin-bottom:4px;padding-bottom:4px;border-bottom:1px solid rgba(255,255,255,0.06);">
            Bank Account Info
          </div>''',
    '''<div class="text-xs text-slate-400 font-bold uppercase tracking-wider mt-4 pb-2 border-b border-white/10">
            Bank Account Info
          </div>'''
)

content = content.replace(
    '''  <!-- Submit Button block -->
  <div style="margin-top:24px; display:flex; justify-content:center; margin-bottom:20px;">
    <button type="submit"
            style="width:100%; max-width:500px; background:linear-gradient(135deg,#7c3aed,#5b21b6); color:white; border:none; border-radius:12px; padding:16px; font-size:15px; font-weight:700; cursor:pointer; font-family:'Inter',sans-serif; transition:all 0.3s ease; box-shadow:0 8px 24px rgba(124,58,237,0.3); display:flex; align-items:center; justify-content:center; gap:8px;"
            onmouseover="this.style.opacity='0.9'; this.style.transform='translateY(-2px)';" onmouseout="this.style.opacity='1'; this.style.transform='translateY(0)';">
      <i class="fa-solid fa-circle-check"></i> Save &amp; Update Profile Details
    </button>
  </div>''',
    '''  <!-- Submit Button block -->
  <div class="mt-6 flex justify-center mb-8">
    <button type="submit" class="w-full max-w-lg bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg shadow-indigo-500/30 hover:-translate-y-0.5 flex items-center justify-center gap-2">
      <i class="fa-solid fa-circle-check"></i> Save &amp; Update Profile Details
    </button>
  </div>'''
)

with open(r'c:\Users\HP\Desktop\MLMP\User\profile.php', 'w', encoding='utf-8') as f:
    f.write(content)
