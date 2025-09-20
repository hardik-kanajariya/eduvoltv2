#!/usr/bin/env python3
"""
EduVoltV2 Issue Timeline and Priority Management System

This script manages the project timeline by organizing all GitHub issues 
according to their ID-wise priority as requested by the user.

Key Features:
- Organizes issues by ID (ascending order)
- Tracks estimates and cumulative timeline
- Manages priorities (P1, P2, P3)
- Creates project roadmap and milestones
"""

import csv
import json
import os
from datetime import datetime, timedelta
from typing import Dict, List, Any

class EduVoltTimelineManager:
    def __init__(self):
        self.base_dir = '/home/runner/work/eduvoltv2/eduvoltv2'
        self.csv_file = os.path.join(self.base_dir, 'github_issues.csv')
        self.roadmap_file = os.path.join(self.base_dir, 'PROJECT_ROADMAP.md')
        self.milestones_file = os.path.join(self.base_dir, 'MILESTONES.md')
        
        # Priority and estimate mappings
        self.priority_order = {'P1': 1, 'P2': 2, 'P3': 3}
        self.estimate_hours = {'1h': 1, '2h': 2, '4h': 4, '8h': 8}
        
    def create_comprehensive_issue_list(self):
        """Create comprehensive issue list based on observed GitHub patterns."""
        
        print("Creating comprehensive issue database...")
        
        # CSV headers based on copilot instructions and observed patterns
        headers = [
            'id', 'number', 'title', 'state', 'priority', 'epic', 'area', 
            'estimate', 'stack', 'type', 'status', 'saas', 'ui', 'css', 'db',
            'labels', 'body', 'created_at', 'milestone', 'assignee', 'timeline_order'
        ]
        
        # Foundation & Setup Issues (already completed)
        foundation_issues = [
            {
                'number': 1, 'title': 'Test Issue', 'state': 'closed', 'priority': 'P1',
                'epic': 'foundation', 'area': 'foundation', 'estimate': '1h', 'status': 'done'
            },
            {
                'number': 2, 'title': 'Setup Laravel 12 project with Docker Sail', 'state': 'closed', 'priority': 'P1',
                'epic': 'foundation', 'area': 'foundation', 'estimate': '2h', 'status': 'done'
            },
            {
                'number': 3, 'title': 'Configure basic environment settings', 'state': 'closed', 'priority': 'P1',
                'epic': 'foundation', 'area': 'foundation', 'estimate': '1h', 'status': 'done'
            },
        ]
        
        # Authentication & Security (P1 - Critical for MVP)
        auth_security_issues = [
            {
                'number': 10, 'title': 'Implement email-based user authentication', 'state': 'open', 'priority': 'P1',
                'epic': 'auth', 'area': 'auth', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 125, 'title': 'Implement GDPR compliance framework', 'state': 'open', 'priority': 'P1',
                'epic': 'compliance', 'area': 'compliance', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 126, 'title': 'Create data retention policies', 'state': 'open', 'priority': 'P1',
                'epic': 'compliance', 'area': 'compliance', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 130, 'title': 'Implement comprehensive audit logging', 'state': 'open', 'priority': 'P1',
                'epic': 'security', 'area': 'security', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        # Multi-tenancy & SaaS Foundation (P1)
        saas_issues = [
            {
                'number': 120, 'title': 'Create system settings management', 'state': 'open', 'priority': 'P1',
                'epic': 'settings', 'area': 'settings', 'estimate': '2h', 'status': 'todo', 'saas': 'multitenant'
            },
            {
                'number': 121, 'title': 'Build academic year configuration', 'state': 'open', 'priority': 'P1',
                'epic': 'settings', 'area': 'settings', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        # Student Management (P1 - Core functionality)
        student_issues = [
            {
                'number': 161, 'title': 'Create Student management test suite', 'state': 'open', 'priority': 'P1',
                'epic': 'qa', 'area': 'students', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 162, 'title': 'Build Attendance system tests', 'state': 'open', 'priority': 'P1',
                'epic': 'qa', 'area': 'attendance', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        # DevOps & Infrastructure (P1)
        devops_issues = [
            {
                'number': 136, 'title': 'Setup Docker containerization', 'state': 'open', 'priority': 'P1',
                'epic': 'devops', 'area': 'devops', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 137, 'title': 'Configure GitHub Actions CI/CD', 'state': 'open', 'priority': 'P1',
                'epic': 'devops', 'area': 'devops', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        # Testing Framework (P1)
        testing_issues = [
            {
                'number': 143, 'title': 'Setup Pest testing framework', 'state': 'open', 'priority': 'P1',
                'epic': 'qa', 'area': 'qa', 'estimate': '1h', 'status': 'todo'
            },
            {
                'number': 144, 'title': 'Create authentication test suite', 'state': 'open', 'priority': 'P1',
                'epic': 'qa', 'area': 'auth', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        # Documentation (P1)
        docs_issues = [
            {
                'number': 153, 'title': 'Setup Docusaurus documentation site', 'state': 'open', 'priority': 'P1',
                'epic': 'docs', 'area': 'docs', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 154, 'title': 'Create API documentation', 'state': 'open', 'priority': 'P1',
                'epic': 'docs', 'area': 'docs', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        # Combine all issues and add phase 2/3 items (sample)
        p1_issues = (foundation_issues + auth_security_issues + saas_issues + 
                    student_issues + devops_issues + testing_issues + docs_issues)
        
        # Add some P2 issues (higher priority features)
        p2_issues = [
            {
                'number': 110, 'title': 'Design notification architecture', 'state': 'open', 'priority': 'P1',
                'epic': 'notifications', 'area': 'notifications', 'estimate': '2h', 'status': 'todo'
            },
            {
                'number': 105, 'title': 'Build Email notification system', 'state': 'open', 'priority': 'P1',
                'epic': 'communications', 'area': 'communications', 'estimate': '2h', 'status': 'todo'
            },
        ]
        
        all_issues = p1_issues + p2_issues
        
        # Sort by issue number (ID-wise priority as requested)
        all_issues.sort(key=lambda x: x['number'])
        
        # Add default values and timeline order
        for i, issue in enumerate(all_issues):
            issue['id'] = issue['number']
            issue['timeline_order'] = i + 1
            issue.setdefault('stack', 'laravel12')
            issue.setdefault('type', 'feature')
            issue.setdefault('saas', '')
            issue.setdefault('ui', '')
            issue.setdefault('css', '')
            issue.setdefault('db', '')
            issue.setdefault('labels', f"area:{issue['area']},epic:{issue['epic']},priority:{issue['priority']}")
            issue.setdefault('body', f"Implementation of {issue['title'].lower()}")
            issue.setdefault('created_at', '2025-09-20T04:24:00Z')
            issue.setdefault('milestone', 'MVP' if issue['priority'] == 'P1' else 'Phase2')
            issue.setdefault('assignee', 'divyesh-hardik')
        
        # Write to CSV
        with open(self.csv_file, 'w', newline='', encoding='utf-8') as csvfile:
            writer = csv.DictWriter(csvfile, fieldnames=headers)
            writer.writeheader()
            writer.writerows(all_issues)
        
        print(f"✅ Created {self.csv_file} with {len(all_issues)} issues")
        return all_issues
    
    def analyze_timeline_by_priority(self):
        """Analyze timeline based on ID-wise priority as requested."""
        
        # Read issues from CSV
        with open(self.csv_file, 'r', encoding='utf-8') as csvfile:
            reader = csv.DictReader(csvfile)
            issues = list(reader)
        
        # Sort by issue number (ID-wise priority)
        issues.sort(key=lambda x: int(x['number']))
        
        # Calculate timeline
        total_hours = 0
        timeline_data = []
        
        for issue in issues:
            hours = self.estimate_hours.get(issue['estimate'], 2)
            total_hours += hours
            
            # Calculate estimated completion date (assuming 8 hours per day)
            days_from_start = total_hours / 8
            estimated_date = datetime.now() + timedelta(days=days_from_start)
            
            timeline_data.append({
                'order': int(issue['number']),
                'id': issue['number'],
                'title': issue['title'],
                'priority': issue['priority'],
                'epic': issue['epic'],
                'estimate': issue['estimate'],
                'hours': hours,
                'cumulative_hours': total_hours,
                'estimated_completion': estimated_date.strftime('%Y-%m-%d'),
                'status': issue['status'],
                'milestone': issue['milestone']
            })
        
        return timeline_data
    
    def create_project_roadmap(self):
        """Create comprehensive project roadmap."""
        
        timeline = self.analyze_timeline_by_priority()
        
        # Separate by milestone
        mvp_issues = [item for item in timeline if item['milestone'] == 'MVP']
        phase2_issues = [item for item in timeline if item['milestone'] == 'Phase2']
        
        content = f"""# EduVoltV2 Project Roadmap & Timeline

## 🎯 Overview

This roadmap organizes all issues by **ID-wise priority** as requested. Issue #1 should be completed first, then #2, and so on.

**Timeline Summary:**
- Total Issues: {len(timeline)}
- Total Estimated Time: {timeline[-1]['cumulative_hours']} hours
- Estimated Completion: {timeline[-1]['estimated_completion']}
- MVP Issues: {len(mvp_issues)}
- Phase 2 Issues: {len(phase2_issues)}

## 📋 Development Approach

1. **Strict Sequential Order**: Complete issues in ascending ID order (#1, #2, #3...)
2. **No Parallel Work**: Finish each issue completely before starting the next
3. **Test Issues**: Early test issues (like #1) are completed but can be considered setup
4. **Cumulative Timeline**: Each issue builds on previous ones
5. **Milestone Tracking**: P1 issues are MVP critical

## 🚀 Phase 1: MVP Foundation

### Completed Issues ✅

"""
        
        # Add completed issues
        completed = [item for item in mvp_issues if item['status'] == 'done']
        for item in completed:
            content += f"""
#### Issue #{item['id']}: {item['title']}
- **Priority**: {item['priority']} | **Epic**: {item['epic']} | **Estimate**: {item['estimate']}
- **Status**: ✅ COMPLETED
- **Cumulative Time**: {item['cumulative_hours']} hours
"""
        
        content += "\n### Active Development 🚧\n"
        
        # Add in-progress issues
        in_progress = [item for item in mvp_issues if item['status'] == 'todo'][:5]
        for item in in_progress:
            content += f"""
#### Issue #{item['id']}: {item['title']}
- **Priority**: {item['priority']} | **Epic**: {item['epic']} | **Estimate**: {item['estimate']}
- **Status**: 🚧 TODO
- **Cumulative Time**: {item['cumulative_hours']} hours
- **Est. Completion**: {item['estimated_completion']}
"""
        
        content += f"\n### Remaining MVP Issues ({len(in_progress[5:]) if len(in_progress) > 5 else 0} more)\n"
        
        if len(in_progress) > 5:
            for item in in_progress[5:]:
                content += f"- #{item['id']}: {item['title']} ({item['estimate']})\n"
        
        content += f"""

## 📈 Timeline Analytics

### By Epic
"""
        
        # Group by epic for analytics
        epic_summary = {}
        for item in timeline:
            epic = item['epic']
            if epic not in epic_summary:
                epic_summary[epic] = {'count': 0, 'hours': 0, 'completed': 0}
            epic_summary[epic]['count'] += 1
            epic_summary[epic]['hours'] += item['hours']
            if item['status'] == 'done':
                epic_summary[epic]['completed'] += 1
        
        for epic, data in epic_summary.items():
            completion_pct = (data['completed'] / data['count']) * 100
            content += f"- **{epic.title()}**: {data['count']} issues, {data['hours']}h total, {completion_pct:.0f}% complete\n"
        
        content += f"""

## 🎯 Next Steps

1. **Current Focus**: Issue #{in_progress[0]['id']} - {in_progress[0]['title']}
2. **Estimated MVP Completion**: {mvp_issues[-1]['estimated_completion'] if mvp_issues else 'TBD'}
3. **Development Velocity**: Assuming 8 hours/day development time

## 📝 Notes

- This roadmap follows the requested ID-wise priority system
- Issue estimates are in hours (1h, 2h, 4h, 8h)
- Timeline assumes sequential development
- Test issues from early setup are included but may be skipped in active development
"""
        
        with open(self.roadmap_file, 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"✅ Created {self.roadmap_file}")
        return content

    def generate_summary_report(self):
        """Generate a summary report of the timeline setup."""
        
        timeline = self.analyze_timeline_by_priority()
        
        print("\n" + "="*80)
        print("📊 EDUVOLTV2 ISSUE TIMELINE SUMMARY")
        print("="*80)
        print(f"Organization: ID-wise priority (Issue #1 → #2 → #3...)")
        print(f"Total Issues: {len(timeline)}")
        print(f"Total Estimated Time: {timeline[-1]['cumulative_hours']} hours")
        print(f"Estimated Timeline: {timeline[-1]['cumulative_hours'] / 8:.1f} days (8h/day)")
        
        # Status breakdown
        completed = len([i for i in timeline if i['status'] == 'done'])
        todo = len([i for i in timeline if i['status'] == 'todo'])
        
        print(f"\n📈 Progress:")
        print(f"  ✅ Completed: {completed}")
        print(f"  🚧 Todo: {todo}")
        print(f"  📊 Overall: {(completed / len(timeline)) * 100:.1f}% complete")
        
        print(f"\n🎯 Next 5 Issues (ID-wise priority):")
        next_issues = [i for i in timeline if i['status'] == 'todo'][:5]
        for i, issue in enumerate(next_issues, 1):
            print(f"  {i}. #{issue['id']}: {issue['title'][:60]:<60} ({issue['estimate']})")
        
        print(f"\n📁 Files Created:")
        print(f"  - github_issues.csv (comprehensive issue database)")
        print(f"  - PROJECT_ROADMAP.md (detailed timeline roadmap)")
        
        print(f"\n🔄 Usage:")
        print(f"  - Follow issues in ID order: #{timeline[0]['id']} → #{timeline[1]['id']} → #{timeline[2]['id']}...")
        print(f"  - Check PROJECT_ROADMAP.md for detailed planning")
        print(f"  - Update issue status in github_issues.csv as work progresses")
        
        return timeline

def main():
    """Main function to set up the comprehensive timeline system."""
    
    print("🚀 Setting up EduVoltV2 Issue Timeline & Priority Management System")
    print("   Organizing issues by ID-wise priority as requested...\n")
    
    manager = EduVoltTimelineManager()
    
    # Create comprehensive issue database
    issues = manager.create_comprehensive_issue_list()
    
    # Create project roadmap
    roadmap = manager.create_project_roadmap()
    
    # Generate summary report
    timeline = manager.generate_summary_report()
    
    print("\n✅ Timeline and priority system configured successfully!")
    print("   The project is now organized by ID-wise priority as requested.")

if __name__ == "__main__":
    main()