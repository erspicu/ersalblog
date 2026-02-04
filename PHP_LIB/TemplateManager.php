<?php
/**
 * ErsalBlog Modern Template Manager
 * A lightweight, logic-aware template engine for <template> based layouts.
 * Compatible with PHP 5.4+
 */

class TemplateManager {
    protected $templates = array();
    protected $rawContent = "";
    
    /**
     * Load a file containing <template id="..."> blocks
     */
    public function load($filepath) {
        if (!file_exists($filepath)) {
            throw new Exception("Template file not found: $filepath");
        }
        $this->rawContent = file_get_contents($filepath);
        $this->parseTemplates($this->rawContent);
    }

    /**
     * Parse <template> tags using Regex (Faster than DOM for fragments)
     */
    protected function parseTemplates($content) {
        // Match <template id="key">content</template>
        // Uses dotall modifier (s) to match across newlines
        if (preg_match_all('/<template\s+id=["\']([^"\']+)["\'][^>]*>(.*?)<\/template>/is', $content, $matches)) {
            foreach ($matches[1] as $index => $id) {
                // Decode specific encoded entities if previously saved by DOM
                $html = str_replace(array('%7B%7B', '%7D%7D'), array('{{', '}}'), $matches[2][$index]);
                $this->templates[$id] = $html;
            }
        }
    }

    /**
     * Render a specific template ID with data
     * @param string $id The template ID (e.g., 'tmpl_post_main')
     * @param array $data Associative array of variables ['title' => 'Hi', 'show' => true]
     * @return string
     */
    public function render($id, $data = array()) {
        if (!isset($this->templates[$id])) {
            return "<!-- Template '$id' not found -->";
        }

        $html = $this->templates[$id];

        // 1. Handle Logic: {{ if var }} ... {{ else }} ... {{ /if }}
        $html = $this->compileConditionals($html, $data);

        // 2. Handle Loops: {{ loop items as item }} ... {{ /loop }}
        $html = $this->compileLoops($html, $data);

        // 3. Handle Variables: {{ var }}
        $html = $this->compileVariables($html, $data);

        return $html;
    }

    /**
     * Compile {{ if key }} logic
     */
    protected function compileConditionals($html, $data) {
        // Pattern: {{ if key }} ... {{ else }} ... {{ /if }}
        // Supports basic boolean check.
        // Nested logic is complex with regex, handling single level for simplicity.
        
        $callback = function($matches) use ($data) {
            $key = trim($matches[1]);
            $inner = $matches[2];
            $truthy = isset($data[$key]) && $data[$key];
            
            // Check for {{ else }}
            $parts = preg_split('/{{\s*else\s*}}/', $inner);
            
            if ($truthy) {
                return $parts[0];
            } else {
                return isset($parts[1]) ? $parts[1] : '';
            }
        };

        // Use preg_replace_callback
        // Pattern: {{ if key }}(content){{ /if }}
        return preg_replace_callback('/{{\s*if\s+([a-zA-Z0-9_]+)\s*}}(.*?){{\s*\/if\s*}}/is', $callback, $html);
    }

    /**
     * Compile {{ loop array as item }} logic
     */
    protected function compileLoops($html, $data) {
        $callback = function($matches) use ($data) {
            $listKey = trim($matches[1]);
            $itemKey = trim($matches[2]);
            $innerTpl = $matches[3];
            $output = "";

            if (isset($data[$listKey]) && is_array($data[$listKey])) {
                foreach ($data[$listKey] as $item) {
                    // Create a scoped data array for the loop item
                    $scopedData = $data; 
                    // If item is array, merge it? Or assign to itemKey?
                    // Blade style: strict assignment.
                    // Simple style: Just assign the value.
                    $scopedData[$itemKey] = $item;
                    
                    // Recursive variable compilation for the inner block
                    // Note: This simple engine doesn't support nested loops efficiently in one pass regex.
                    $output .= $this->compileVariables($innerTpl, $scopedData);
                }
            }
            return $output;
        };

        return preg_replace_callback('/{{\s*loop\s+([a-zA-Z0-9_]+)\s+as\s+([a-zA-Z0-9_]+)\s*}}(.*?){{\s*\/loop\s*}}/is', $callback, $html);
    }

    /**
     * Compile {{ key }} variables
     */
    protected function compileVariables($html, $data) {
        // Find all {{ var }}
        if (preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', $html, $matches)) {
            $searches = array();
            $replaces = array();
            
            foreach ($matches[1] as $key) {
                if (isset($data[$key])) {
                    $val = $data[$key];
                    if (is_array($val)) continue; // Don't print arrays
                    $searches[] = "{{ $key }}";
                    $searches[] = "{{$key}}"; // Handle tight spacing
                    $replaces[] = $val;
                    $replaces[] = $val;
                }
            }
            // Use str_replace for speed on matched keys
            // But beware of duplicates. Unique them.
            if (!empty($searches)) {
                $html = str_replace($searches, $replaces, $html);
            }
        }
        return $html;
    }

    /**
     * Helper to retrieve all IDs (for debugging)
     */
    public function getTemplateIds() {
        return array_keys($this->templates);
    }
}
