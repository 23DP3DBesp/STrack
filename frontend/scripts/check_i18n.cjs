const fs = require('fs')
const path = require('path')
const vm = require('vm')

const filePath = path.join(__dirname, '..', 'src', 'i18n', 'index.js')
const src = fs.readFileSync(filePath, 'utf8')

const marker = 'const messages ='
const idx = src.indexOf(marker)
if (idx === -1) {
  console.error('messages marker not found')
  process.exit(2)
}
let braceStart = src.indexOf('{', idx)
if (braceStart === -1) {
  console.error('opening brace for messages not found')
  process.exit(2)
}
let i = braceStart
let depth = 0
let end = -1
for (; i < src.length; i++) {
  const ch = src[i]
  if (ch === '{') depth++
  else if (ch === '}') {
    depth--
    if (depth === 0) {
      end = i
      break
    }
  }
}
if (end === -1) {
  console.error('could not find end of messages object')
  process.exit(2)
}
const objText = src.slice(braceStart, end + 1)
let messages
try {
  messages = vm.runInNewContext('(' + objText + ')')
} catch (err) {
  console.error('eval error:', err)
  process.exit(2)
}

function compare(a, b, prefix = '') {
  const missing = []
  for (const key of Object.keys(a)) {
    const p = prefix ? prefix + '.' + key : key
    if (!(key in b)) {
      missing.push(p)
    } else {
      const va = a[key]
      const vb = b[key]
      if (va && typeof va === 'object' && !Array.isArray(va)) {
        if (vb && typeof vb === 'object' && !Array.isArray(vb)) {
          missing.push(...compare(va, vb, p))
        } else {
          missing.push(p)
        }
      }
    }
  }
  return missing
}

const en = messages.en || {}
const lv = messages.lv || {}
const missingInLv = compare(en, lv)
const missingInEn = compare(lv, en)

console.log('Missing keys in lv compared to en:', missingInLv.length)
missingInLv.forEach((k) => console.log('  -', k))
console.log('\nMissing keys in en compared to lv:', missingInEn.length)
missingInEn.forEach((k) => console.log('  -', k))

if (missingInLv.length === 0) {
  console.log('\nAll English keys have Latvian translations.')
} else {
  console.log('\nSome English keys are missing in Latvian.')
}

process.exit(missingInLv.length === 0 ? 0 : 1)
