import test from 'node:test';
import assert from 'node:assert/strict';
import { keepDialogFocus } from '../../resources/js/dialogFocus.js';
function fixture() {
  const doc = { activeElement: null };
  const control = (options = {}) => ({ disabled: false, tabIndex: 0, getClientRects: () => [1], focus() { doc.activeElement = this; }, ...options });
  const first = control(), middle = control(), last = control();
  const disabled = control({disabled:true}), hidden = control({getClientRects:()=>[]}), negative = control({tabIndex:-1});
  const dialog = {ownerDocument:doc, querySelectorAll:()=>[hidden,first,middle,last,disabled,negative]};
  const event = (shiftKey=false, key='Tab') => ({key,shiftKey,currentTarget:dialog,prevented:false,preventDefault(){this.prevented=true;}});
  return {doc,first,middle,last,event,dialog};
}
test('Shift+Tab from the first visible control wraps past hidden and disabled controls',()=>{const f=fixture();f.first.focus();const e=f.event(true);keepDialogFocus(e);assert.equal(f.doc.activeElement,f.last);assert.equal(e.prevented,true);});
test('Tab from the last control wraps to the first',()=>{const f=fixture();f.last.focus();const e=f.event();keepDialogFocus(e);assert.equal(f.doc.activeElement,f.first);assert.equal(e.prevented,true);});
test('Interior navigation and Escape remain native',()=>{const f=fixture();f.middle.focus();const e=f.event();keepDialogFocus(e);assert.equal(e.prevented,false);f.last.focus();const escape=f.event(false,'Escape');keepDialogFocus(escape);assert.equal(escape.prevented,false);});
test('A dialog with no enabled controls does not leak Tab',()=>{const f=fixture();f.dialog.querySelectorAll=()=>[];const e=f.event();keepDialogFocus(e);assert.equal(e.prevented,true);});
