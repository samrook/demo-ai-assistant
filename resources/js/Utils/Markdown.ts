import MarkdownIt from 'markdown-it';
import { createHighlighter } from 'shiki';

const md = new MarkdownIt({
    html: false,
});
let highlighter: any = null;

export async function renderMarkdown(content: string) {
    // Only initialize the highlighter once for better performance
    if (!highlighter) {
        highlighter = await createHighlighter({
            themes: ['nord'],
            // Pre-load common languages your AI might output
            langs: ['php', 'javascript', 'typescript', 'vue', 'html', 'css', 'bash', 'json', 'sql'] 
        });
    }
    
    md.set({
        highlight: (code, lang) => {
            try {
                // Modern Shiki requires the theme to be explicitly passed here
                return highlighter.codeToHtml(code, { 
                    lang: lang || 'text', 
                    theme: 'nord' 
                });
            } catch (e) {
                // Markdown-it treats highlight output as HTML, so escape fallback content.
                const safeCode = md.utils.escapeHtml(code);
                const safeLang = md.utils.escapeHtml(lang || 'text');

                return `<pre><code class="language-${safeLang}">${safeCode}</code></pre>`;
            }
        }
    });

    return md.render(content);
}
