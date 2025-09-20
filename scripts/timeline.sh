#!/bin/bash

# EduVoltV2 Issue Timeline Management Scripts
# Collection of utilities to manage the ID-wise priority system

echo "🚀 EduVoltV2 Issue Timeline & Priority Management Tools"
echo "================================================================"

# Function to show current status
show_status() {
    echo "📊 Current Project Status:"
    if [ -f "github_issues.csv" ]; then
        completed=$(grep ",done," github_issues.csv | wc -l)
        todo=$(grep ",todo," github_issues.csv | wc -l)
        total=$((completed + todo))
        
        echo "  ✅ Completed: $completed issues"
        echo "  🚧 Todo: $todo issues" 
        echo "  📈 Progress: $((completed * 100 / total))% complete"
        
        echo ""
        echo "🎯 Next 3 Issues (ID-wise priority):"
        grep ",todo," github_issues.csv | head -3 | while IFS=',' read -r id number title rest; do
            echo "  • #$number: $(echo $title | cut -c1-50)"
        done
    else
        echo "  ❌ github_issues.csv not found. Run: python3 scripts/issue_timeline.py"
    fi
}

# Function to mark issue as completed
complete_issue() {
    local issue_id=$1
    if [ -z "$issue_id" ]; then
        echo "❌ Usage: $0 complete <issue_number>"
        return 1
    fi
    
    if [ -f "github_issues.csv" ]; then
        # Create backup
        cp github_issues.csv github_issues.csv.bak
        
        # Update status to done
        sed -i "s/^$issue_id,$issue_id,\([^,]*\),open,\([^,]*\),\([^,]*\),\([^,]*\),\([^,]*\),\([^,]*\),\([^,]*\),todo,/$issue_id,$issue_id,\1,closed,\2,\3,\4,\5,\6,\7,done,/" github_issues.csv
        
        echo "✅ Marked issue #$issue_id as completed"
        echo "💾 Backup created: github_issues.csv.bak"
        
        # Regenerate roadmap
        if [ -f "scripts/issue_timeline.py" ]; then
            echo "🔄 Regenerating roadmap..."
            python3 scripts/issue_timeline.py > /dev/null 2>&1
            echo "✅ PROJECT_ROADMAP.md updated"
        fi
    else
        echo "❌ github_issues.csv not found"
    fi
}

# Function to show next issue details
next_issue() {
    echo "🎯 Next Issue to Work On:"
    if [ -f "github_issues.csv" ]; then
        next=$(grep ",todo," github_issues.csv | head -1)
        if [ -n "$next" ]; then
            IFS=',' read -r id number title state priority epic area estimate stack type status rest <<< "$next"
            echo "  📌 Issue #$number: $title"
            echo "  🏷️  Priority: $priority | Epic: $epic | Area: $area"
            echo "  ⏱️  Estimate: $estimate"
            echo "  🔗 GitHub: https://github.com/hardik-kanajariya/eduvoltv2/issues/$number"
        else
            echo "  🎉 All issues completed!"
        fi
    else
        echo "  ❌ github_issues.csv not found"
    fi
}

# Function to create progress report
progress_report() {
    echo "📈 Generating Progress Report..."
    
    if [ -f "github_issues.csv" ]; then
        echo ""
        echo "## EduVoltV2 Progress Report - $(date '+%Y-%m-%d')"
        echo ""
        
        # Overall stats
        completed=$(grep ",done," github_issues.csv | wc -l)
        todo=$(grep ",todo," github_issues.csv | wc -l)
        total=$((completed + todo))
        
        echo "### Overall Progress"
        echo "- **Total Issues**: $total"
        echo "- **Completed**: $completed ($(((completed * 100) / total))%)"
        echo "- **Remaining**: $todo"
        echo ""
        
        # By epic
        echo "### Progress by Epic"
        grep -v "^id," github_issues.csv | cut -d',' -f6 | sort | uniq | while read epic; do
            if [ -n "$epic" ]; then
                epic_total=$(grep ",$epic," github_issues.csv | wc -l)
                epic_done=$(grep ",$epic," github_issues.csv | grep ",done," | wc -l)
                echo "- **$epic**: $epic_done/$epic_total ($(((epic_done * 100) / epic_total))%)"
            fi
        done
        
        echo ""
        echo "### Next Priority Issues"
        grep ",todo," github_issues.csv | head -5 | while IFS=',' read -r id number title rest; do
            echo "- [ ] #$number: $title"
        done
        
    else
        echo "❌ github_issues.csv not found"
    fi
}

# Function to validate timeline
validate_timeline() {
    echo "🔍 Validating Timeline Integrity..."
    
    if [ -f "github_issues.csv" ]; then
        # Check for gaps in ID sequence
        echo "Checking for ID sequence gaps..."
        issue_ids=$(grep -v "^id," github_issues.csv | cut -d',' -f2 | sort -n)
        
        prev_id=0
        gaps_found=false
        
        for id in $issue_ids; do
            if [ $((id - prev_id)) -gt 1 ] && [ $prev_id -gt 0 ]; then
                echo "  ⚠️  Gap found: Missing issues between #$prev_id and #$id"
                gaps_found=true
            fi
            prev_id=$id
        done
        
        if [ "$gaps_found" = false ]; then
            echo "  ✅ No gaps found in ID sequence"
        fi
        
        # Check for duplicate IDs
        echo "Checking for duplicate IDs..."
        duplicates=$(grep -v "^id," github_issues.csv | cut -d',' -f2 | sort | uniq -d)
        if [ -n "$duplicates" ]; then
            echo "  ⚠️  Duplicate IDs found: $duplicates"
        else
            echo "  ✅ No duplicate IDs found"
        fi
        
        # Check timeline order
        echo "Checking timeline order..."
        if grep -v "^id," github_issues.csv | cut -d',' -f2 | sort -n -c 2>/dev/null; then
            echo "  ✅ Issues are properly ordered by ID"
        else
            echo "  ⚠️  Issues are not in proper ID order"
        fi
        
    else
        echo "❌ github_issues.csv not found"
    fi
}

# Main command handler
case "$1" in
    "status")
        show_status
        ;;
    "complete")
        complete_issue "$2"
        ;;
    "next")
        next_issue
        ;;
    "report")
        progress_report
        ;;
    "validate")
        validate_timeline
        ;;
    "regenerate")
        if [ -f "scripts/issue_timeline.py" ]; then
            echo "🔄 Regenerating timeline system..."
            python3 scripts/issue_timeline.py
        else
            echo "❌ scripts/issue_timeline.py not found"
        fi
        ;;
    *)
        echo "Usage: $0 {status|complete|next|report|validate|regenerate}"
        echo ""
        echo "Commands:"
        echo "  status     - Show current project status"
        echo "  complete   - Mark an issue as completed (e.g., complete 10)"
        echo "  next       - Show next issue to work on"
        echo "  report     - Generate progress report"
        echo "  validate   - Validate timeline integrity"
        echo "  regenerate - Regenerate timeline files"
        echo ""
        echo "Examples:"
        echo "  $0 status"
        echo "  $0 complete 10"
        echo "  $0 next"
        ;;
esac