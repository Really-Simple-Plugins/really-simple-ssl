"use strict";

exports.__esModule = true;
exports.default = void 0;

async function toDisplayDialog(page, block) {
  return new Promise((resolve, reject) => {
    const handleDialog = dialog => {
      page.removeListener('dialog', handleDialog);
      resolve(dialog);
    };

    page.on('dialog', handleDialog);
    block().catch(reject);
  });
}

var _default = toDisplayDialog;
exports.default = _default;