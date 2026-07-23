const { icons } = require('lucide-react');

const list = ['FileSpreadsheet', 'ReceiptText'];
list.forEach(name => {
  const i = icons[name];
  console.log(name + ':');
  const innerSvg = i.map(node => {
    return `<${node[0]} ${Object.entries(node[1]).map(([k, v]) => `${k}="${v}"`).join(' ')}/>`;
  }).join('');
  
  console.log('<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' + innerSvg + '</svg>');
});
