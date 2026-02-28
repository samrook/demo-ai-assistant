import MarkdownIt from 'markdown-it';
import { createHighlighter } from 'shiki';

const md = new MarkdownIt();
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
                // Fallback to plain text if the AI spits out an unknown language
                return code;
            }
        }
    });

    return md.render(content);
}