#!/usr/bin/env node

/**
 * Helper script to determine which steering files should be loaded for a given task
 * Usage: node .kiro/scripts/get-steering-files.js <task-category-or-spec-name>
 */

const fs = require('fs');
const path = require('path');

// Load the task-steering mapping
const mappingPath = path.join(__dirname, '..', 'system', 'task-steering-mapping.json');
const mapping = JSON.parse(fs.readFileSync(mappingPath, 'utf8'));

function getSteeringFiles(input) {
    const result = {
        common: mapping.common_files,
        specific: [],
        categories: []
    };

    // Check if input is a spec name
    if (mapping.spec_mappings[input]) {
        const categories = mapping.spec_mappings[input];
        result.categories = categories;
        
        // Collect all steering files for these categories
        const steeringFiles = new Set();
        categories.forEach(category => {
            if (mapping.task_categories[category]) {
                mapping.task_categories[category].steering_files.forEach(file => {
                    steeringFiles.add(file);
                });
            }
        });
        result.specific = Array.from(steeringFiles);
    }
    // Check if input is a task category
    else if (mapping.task_categories[input]) {
        result.categories = [input];
        result.specific = mapping.task_categories[input].steering_files;
    }
    // Try to find partial matches
    else {
        const matches = Object.keys(mapping.task_categories).filter(category => 
            category.includes(input.toLowerCase()) || input.toLowerCase().includes(category)
        );
        
        if (matches.length > 0) {
            result.categories = matches;
            const steeringFiles = new Set();
            matches.forEach(category => {
                mapping.task_categories[category].steering_files.forEach(file => {
                    steeringFiles.add(file);
                });
            });
            result.specific = Array.from(steeringFiles);
        }
    }

    return result;
}

function displayResults(input, result) {
    console.log(`\n🎯 Steering Files for: ${input}`);
    console.log('=' .repeat(50));
    
    if (result.categories.length === 0) {
        console.log('❌ No matching categories found');
        console.log('\nAvailable categories:');
        Object.keys(mapping.task_categories).forEach(category => {
            console.log(`  - ${category}: ${mapping.task_categories[category].description}`);
        });
        console.log('\nAvailable specs:');
        Object.keys(mapping.spec_mappings).forEach(spec => {
            console.log(`  - ${spec}`);
        });
        return;
    }

    console.log(`\n📂 Categories: ${result.categories.join(', ')}`);
    
    console.log('\n📋 Common Files (always load):');
    result.common.forEach(file => {
        console.log(`  ✓ ${file}`);
    });
    
    console.log('\n🎯 Task-Specific Files:');
    result.specific.forEach(file => {
        console.log(`  ✓ ${file}`);
    });
    
    console.log(`\n📊 Total files to load: ${result.common.length + result.specific.length}`);
    console.log(`📊 Files skipped: ${Object.keys(mapping.task_categories).reduce((total, cat) => 
        total + mapping.task_categories[cat].steering_files.length, 0) - result.specific.length}`);
}

// Main execution
const input = process.argv[2];

if (!input) {
    console.log('Usage: node get-steering-files.js <task-category-or-spec-name>');
    console.log('\nExamples:');
    console.log('  node get-steering-files.js authentication');
    console.log('  node get-steering-files.js filament');
    console.log('  node get-steering-files.js authentication-testing');
    console.log('  node get-steering-files.js universal-utility-management');
    process.exit(1);
}

const result = getSteeringFiles(input);
displayResults(input, result);

// Export for programmatic use
module.exports = { getSteeringFiles, mapping };