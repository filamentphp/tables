import table from './components/table.js'

document.addEventListener('alpine:init', () => {
    window.Alpine.data('filamentTable', table)
})
