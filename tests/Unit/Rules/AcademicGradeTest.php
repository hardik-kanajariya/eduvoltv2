<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\AcademicGrade;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for AcademicGrade validation rule.
 */
class AcademicGradeTest extends TestCase
{
    public function test_validates_percentage_grades(): void
    {
        $rule = AcademicGrade::percentage();
        $passes = true;

        // Valid percentages
        $rule->validate('grade', '85.5', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('grade', 100, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('grade', 0, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);
    }

    public function test_rejects_invalid_percentage_grades(): void
    {
        $rule = AcademicGrade::percentage();
        $passes = true;

        // Invalid percentages
        $rule->validate('grade', 101, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);

        $passes = true;
        $rule->validate('grade', -5, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);

        $passes = true;
        $rule->validate('grade', 'invalid', function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);
    }

    public function test_validates_gpa_grades(): void
    {
        $rule = AcademicGrade::gpa();
        $passes = true;

        // Valid GPA
        $rule->validate('gpa', '3.75', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('gpa', 4.0, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('gpa', 0.0, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);
    }

    public function test_rejects_invalid_gpa_grades(): void
    {
        $rule = AcademicGrade::gpa();
        $passes = true;

        // Invalid GPA
        $rule->validate('gpa', 4.5, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);

        $passes = true;
        $rule->validate('gpa', -1, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);
    }

    public function test_validates_letter_grades(): void
    {
        $rule = AcademicGrade::letterGrade();
        $passes = true;

        // Valid letter grades
        $rule->validate('grade', 'A', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('grade', 'B+', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('grade', 'F', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);
    }

    public function test_rejects_invalid_letter_grades(): void
    {
        $rule = AcademicGrade::letterGrade();
        $passes = true;

        // Invalid letter grades
        $rule->validate('grade', 'Z', function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);

        $passes = true;
        $rule->validate('grade', 'A++', function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);

        $passes = true;
        $rule->validate('grade', 123, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);
    }

    public function test_validates_custom_scale(): void
    {
        $rule = AcademicGrade::customScale(1.0, 10.0);
        $passes = true;

        // Valid custom scale values
        $rule->validate('grade', 5.5, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('grade', 1.0, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $rule->validate('grade', 10.0, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);
    }

    public function test_rejects_invalid_custom_scale(): void
    {
        $rule = AcademicGrade::customScale(1.0, 10.0);
        $passes = true;

        // Invalid custom scale values
        $rule->validate('grade', 0.5, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);

        $passes = true;
        $rule->validate('grade', 11.0, function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes);
    }

    public function test_validates_specific_education_systems(): void
    {
        // German grading system (1-6, where 1 is best)
        $germanRule = AcademicGrade::forSystem('german_grade');
        $passes = true;

        $germanRule->validate('grade', 2.5, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        // French grading system (0-20)
        $frenchRule = AcademicGrade::forSystem('french_grade');
        $passes = true;

        $frenchRule->validate('grade', 15.5, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);
    }

    public function test_handles_empty_values(): void
    {
        $rule = AcademicGrade::percentage();
        $passes = true;

        $rule->validate('grade', '', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes); // Empty values should pass

        $rule->validate('grade', null, function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes); // Null values should pass
    }

    public function test_grade_conversion(): void
    {
        // Test GPA to percentage conversion
        $percentage = AcademicGrade::convertGrade(3.0, 'gpa', 'percentage');
        $this->assertEquals(75.0, $percentage);

        // Test percentage to GPA conversion
        $gpa = AcademicGrade::convertGrade(85, 'percentage', 'gpa');
        $this->assertEquals(3.4, $gpa);

        // Test French grade to percentage
        $percentage = AcademicGrade::convertGrade(16, 'french_grade', 'percentage');
        $this->assertEquals(80.0, $percentage);
    }

    public function test_custom_letter_grades(): void
    {
        $customGrades = ['Excellent', 'Good', 'Average', 'Poor'];
        $rule = AcademicGrade::letterGrade($customGrades);
        $passes = true;

        $rule->validate('grade', 'Excellent', function () use (&$passes) {
            $passes = false;
        });
        $this->assertTrue($passes);

        $passes = true;
        $rule->validate('grade', 'A', function () use (&$passes) {
            $passes = false;
        });
        $this->assertFalse($passes); // Should fail as 'A' is not in custom grades
    }
}
