#!/usr/bin/env python3
"""
Python script to determine which steering files should be loaded for a given task
Usage: python get_steering_files.py <task-category-or-spec-name> [--list] [--all]
"""

import json
import os
import sys
import argparse
from pathlib import Path

def load_mapping():
    """Load the task-steering mapping configuration"""
    script_dir = Path(__file__).parent
    kiro_dir = script_dir.parent
    mapping_path = kiro_dir / "system" / "task-steering-mapping.json"
    
    if not mapping_path.exists():
        print(f"Error: Task-steering mapping file not found: {mapping_path}")
        sys.exit(1)
    
    try:
        with open(mapping_path, 'r', encoding='utf-8') as f:
            return json.load(f)
    except Exception as e:
        print(f"Error: Failed to parse mapping file: {e}")
        sys.exit(1)

def get_steering_files(input_task, mapping):
    """Determine which steering files are needed for the given task"""
    result = {
        'common': mapping['common_files'],
        'specific': [],
        'categories': [],
        'description': ''
    }
    
    # Check if input is a spec name
    if input_task in mapping['spec_mappings']:
        categories = mapping['spec_mappings'][input_task]
        result['categories'] = categories
        
        # Collect all steering files for these categories
        steering_files = set()
        for category in categories:
            if category in mapping['task_categories']:
                steering_files.update(mapping['task_categories'][category]['steering_files'])
        
        result['specific'] = sorted(list(steering_files))
        result['description'] = f"Multi-category spec: {', '.join(categories)}"
    
    # Check if input is a task category
    elif input_task in mapping['task_categories']:
        result['categories'] = [input_task]
        result['specific'] = mapping['task_categories'][input_task]['steering_files']
        result['description'] = mapping['task_categories'][input_task]['description']
    
    # Try to find partial matches
    else:
        matches = [cat for cat in mapping['task_categories'].keys() 
                  if input_task.lower() in cat.lower() or cat.lower() in input_task.lower()]
        
        if matches:
            result['categories'] = matches
            steering_files = set()
            for category in matches:
                steering_files.update(mapping['task_categories'][category]['steering_files'])
            
            result['specific'] = sorted(list(steering_files))
            result['description'] = f"Partial match for: {', '.join(matches)}"
    
    return result

def check_file_exists(filename, steering_dir):
    """Check if a steering file exists"""
    file_path = steering_dir / filename
    return file_path.exists()

def show_results(input_task, result, mapping):
    """Display the results in a formatted way"""
    script_dir = Path(__file__).parent
    kiro_dir = script_dir.parent
    steering_dir = kiro_dir / "steering"
    
    print(f"\n🎯 Target Steering Files for: {input_task}")
    print("=" * 50)
    
    if not result['categories']:
        print("❌ No matching categories found")
        print("\nAvailable categories:")
        for category, data in mapping['task_categories'].items():
            print(f"  - {category}: {data['description']}")
        print("\nAvailable specs:")
        for spec in mapping['spec_mappings'].keys():
            print(f"  - {spec}")
        return
    
    print(f"\n📂 Categories: {', '.join(result['categories'])}")
    if result['description']:
        print(f"📝 Description: {result['description']}")
    
    print("\n📋 Common Files (always load):")
    for file in result['common']:
        exists = check_file_exists(file, steering_dir)
        status = "✓" if exists else "❌"
        print(f"  {status} {file}")
    
    print("\n🎯 Task-Specific Files:")
    for file in result['specific']:
        exists = check_file_exists(file, steering_dir)
        status = "✓" if exists else "❌"
        print(f"  {status} {file}")
    
    total_files = len(result['common']) + len(result['specific'])
    all_steering_files = sum(len(data['steering_files']) 
                           for data in mapping['task_categories'].values())
    skipped_files = all_steering_files - len(result['specific'])
    efficiency_gain = round((1 - total_files / (all_steering_files + len(result['common']))) * 100, 1)
    
    print(f"\n📊 Summary:")
    print(f"  Total files to load: {total_files}")
    print(f"  Files skipped: {skipped_files}")
    print(f"  Efficiency gain: {efficiency_gain}%")

def show_all_categories(mapping):
    """Show all available categories and mappings"""
    print("\n📚 All Available Task Categories:")
    print("=" * 60)
    
    for category, data in sorted(mapping['task_categories'].items()):
        print(f"\n🏷️  {category}")
        print(f"   Description: {data['description']}")
        print(f"   Files ({len(data['steering_files'])}):")
        for file in data['steering_files']:
            print(f"     - {file}")
    
    print("\n📋 Spec Mappings:")
    print("=" * 30)
    for spec, categories in sorted(mapping['spec_mappings'].items()):
        print(f"  {spec} → {', '.join(categories)}")

def main():
    parser = argparse.ArgumentParser(description='Determine steering files for Kiro tasks')
    parser.add_argument('task_input', nargs='?', help='Task category or spec name')
    parser.add_argument('--list', action='store_true', help='List files only (for programmatic use)')
    parser.add_argument('--all', action='store_true', help='Show all categories and mappings')
    
    args = parser.parse_args()
    
    mapping = load_mapping()
    
    if args.all:
        show_all_categories(mapping)
        return
    
    if not args.task_input:
        print("Usage: python get_steering_files.py <task-category-or-spec-name> [--list] [--all]")
        print("\nExamples:")
        print("  python get_steering_files.py authentication")
        print("  python get_steering_files.py filament")
        print("  python get_steering_files.py authentication-testing")
        print("  python get_steering_files.py universal-utility-management")
        print("  python get_steering_files.py --all")
        sys.exit(1)
    
    result = get_steering_files(args.task_input, mapping)
    
    if args.list:
        if result['categories']:
            all_files = sorted(set(result['common'] + result['specific']))
            for file in all_files:
                print(file)
        sys.exit(0 if result['categories'] else 1)
    
    show_results(args.task_input, result, mapping)
    sys.exit(0 if result['categories'] else 1)

if __name__ == '__main__':
    main()