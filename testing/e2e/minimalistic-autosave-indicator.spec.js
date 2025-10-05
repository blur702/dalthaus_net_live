const { test, expect } = require('@playwright/test');

test.describe('Minimalistic Autosave Indicator', () => {
  test('should display subtle, non-obtrusive autosave indicator with proper visual design', async ({ page }) => {
    // Set viewport for consistent testing
    await page.setViewportSize({ width: 1280, height: 720 });

    // Navigate to test HTML page with minimalistic autosave indicator
    await page.goto('file:///Users/kevin/Downloads/dalthaus_net/dalthaus_net_live/test_autosave_minimalistic.html');
    await page.waitForLoadState('networkidle');

    // Wait for page to fully load and autosave to initialize
    await page.waitForTimeout(1000);

    // Take initial screenshot showing the autosaveStatus element
    await page.screenshot({ 
      path: 'testing/testing/screenshots/minimalistic-indicator-initial.png',
      fullPage: true 
    });

    // Verify autosaveStatus element exists (initially empty but element is present)
    const autosaveStatus = page.locator('#autosaveStatus');
    await expect(autosaveStatus).toBeAttached();

    console.log('✓ AutosaveStatus element is present');

    // Measure initial styling and positioning
    const initialStyles = await autosaveStatus.evaluate(el => {
      const styles = window.getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return {
        fontSize: styles.fontSize,
        opacity: styles.opacity,
        color: styles.color,
        fontWeight: styles.fontWeight,
        position: {
          width: rect.width,
          height: rect.height,
          top: rect.top,
          left: rect.left
        }
      };
    });

    console.log('Initial indicator styles:', initialStyles);

    // Type a title to start autosave
    await page.fill('input[name="title"]', 'Minimalistic Test');
    
    // Wait briefly and check for "Active" indicator
    await page.waitForTimeout(500);
    
    // Look for "Active" text (should appear briefly)
    const activeText = await autosaveStatus.textContent();
    console.log('Indicator text after title input:', activeText);

    // Take screenshot of active state
    await page.screenshot({ 
      path: 'testing/testing/screenshots/minimalistic-indicator-active.png',
      fullPage: false,
      clip: { x: 0, y: 0, width: 1280, height: 200 }
    });

    // Focus on content area and add text to trigger autosave
    await page.fill('textarea[name="body"]', 'This is test content to trigger the minimalistic autosave indicator. Testing the subtle design and behavior.');

    // Wait for debounce period
    await page.waitForTimeout(2500);

    // Check for "Saving" state
    let savingDetected = false;
    let savingScreenshotTaken = false;

    // Monitor for saving state
    for (let i = 0; i < 10; i++) {
      const currentText = await autosaveStatus.textContent();
      console.log(`Check ${i + 1}: Indicator text: "${currentText}"`);
      
      if (currentText && currentText.toLowerCase().includes('saving')) {
        savingDetected = true;
        if (!savingScreenshotTaken) {
          await page.screenshot({ 
            path: 'testing/testing/screenshots/minimalistic-indicator-saving.png',
            fullPage: false,
            clip: { x: 0, y: 0, width: 1280, height: 200 }
          });
          savingScreenshotTaken = true;
          console.log('✓ Captured saving state screenshot');
        }
      }
      
      await page.waitForTimeout(300);
    }

    // Wait for save completion and time display
    await page.waitForTimeout(3000);

    // Check for time display (successful save)
    const finalText = await autosaveStatus.textContent();
    console.log('Final indicator text:', finalText);

    // Take screenshot of completed save state
    await page.screenshot({ 
      path: 'testing/testing/screenshots/minimalistic-indicator-saved.png',
      fullPage: false,
      clip: { x: 0, y: 0, width: 1280, height: 200 }
    });

    // Analyze the dot indicator (should be a small element within autosaveStatus)
    const dotElement = await autosaveStatus.locator('.autosave-dot, .dot, [class*="dot"]').first();
    let dotStyles = null;
    
    try {
      await expect(dotElement).toBeVisible();
      dotStyles = await dotElement.evaluate(el => {
        const styles = window.getComputedStyle(el);
        const rect = el.getBoundingClientRect();
        return {
          width: styles.width,
          height: styles.height,
          borderRadius: styles.borderRadius,
          backgroundColor: styles.backgroundColor,
          opacity: styles.opacity,
          size: {
            width: rect.width,
            height: rect.height
          }
        };
      });
      console.log('Dot element styles:', dotStyles);
    } catch (error) {
      console.log('Dot element not found or not visible, checking for inline styles...');
      
      // Check if dot is implemented as inline element or pseudo-element
      const statusHTML = await autosaveStatus.innerHTML();
      console.log('AutosaveStatus HTML content:', statusHTML);
    }

    // Test hover effect for opacity change
    console.log('Testing hover effect...');
    
    // Get initial opacity
    const initialOpacity = await autosaveStatus.evaluate(el => {
      return window.getComputedStyle(el).opacity;
    });
    
    // Hover over the element
    await autosaveStatus.hover();
    await page.waitForTimeout(300);
    
    // Get hover opacity
    const hoverOpacity = await autosaveStatus.evaluate(el => {
      return window.getComputedStyle(el).opacity;
    });
    
    console.log(`Opacity: Initial=${initialOpacity}, Hover=${hoverOpacity}`);

    // Take screenshot during hover
    await page.screenshot({ 
      path: 'testing/testing/screenshots/minimalistic-indicator-hover.png',
      fullPage: false,
      clip: { x: 0, y: 0, width: 1280, height: 200 }
    });

    // Measure overall indicator dimensions and positioning
    const finalMeasurements = await autosaveStatus.evaluate(el => {
      const styles = window.getComputedStyle(el);
      const rect = el.getBoundingClientRect();
      return {
        dimensions: {
          width: rect.width,
          height: rect.height
        },
        positioning: {
          top: rect.top,
          left: rect.left,
          position: styles.position
        },
        typography: {
          fontSize: styles.fontSize,
          fontWeight: styles.fontWeight,
          fontFamily: styles.fontFamily
        },
        colors: {
          color: styles.color,
          backgroundColor: styles.backgroundColor
        },
        visibility: {
          opacity: styles.opacity,
          visibility: styles.visibility,
          display: styles.display
        }
      };
    });

    console.log('Final measurements:', JSON.stringify(finalMeasurements, null, 2));

    // Verify minimalistic design criteria
    const designAnalysis = {
      isMinimalistic: true,
      issues: [],
      strengths: []
    };

    // Check font size (should be small)
    const fontSize = parseFloat(finalMeasurements.typography.fontSize);
    if (fontSize <= 14) {
      designAnalysis.strengths.push(`Small font size: ${fontSize}px`);
    } else {
      designAnalysis.issues.push(`Font size too large: ${fontSize}px`);
      designAnalysis.isMinimalistic = false;
    }

    // Check opacity (should be muted when not hovered)
    const opacity = parseFloat(initialOpacity);
    if (opacity <= 0.8) {
      designAnalysis.strengths.push(`Muted opacity: ${opacity}`);
    } else {
      designAnalysis.issues.push(`Opacity too prominent: ${opacity}`);
    }

    // Check hover effect
    if (parseFloat(hoverOpacity) > parseFloat(initialOpacity)) {
      designAnalysis.strengths.push(`Hover effect increases opacity: ${initialOpacity} → ${hoverOpacity}`);
    }

    // Check dimensions (should be compact)
    if (finalMeasurements.dimensions.height <= 30) {
      designAnalysis.strengths.push(`Compact height: ${finalMeasurements.dimensions.height}px`);
    } else {
      designAnalysis.issues.push(`Height too large: ${finalMeasurements.dimensions.height}px`);
    }

    // Verify time format is displayed
    const timeRegex = /\d{1,2}:\d{2}\s?(AM|PM)/i;
    if (timeRegex.test(finalText)) {
      designAnalysis.strengths.push(`Shows time format: "${finalText}"`);
    } else {
      designAnalysis.issues.push(`Time format not detected: "${finalText}"`);
    }

    // Final comprehensive screenshot
    await page.screenshot({ 
      path: 'testing/testing/screenshots/minimalistic-indicator-final.png',
      fullPage: true 
    });

    // Output test results
    console.log('\n=== MINIMALISTIC AUTOSAVE INDICATOR TEST RESULTS ===');
    console.log(`Overall Assessment: ${designAnalysis.isMinimalistic ? 'PASSES' : 'NEEDS IMPROVEMENT'}`);
    console.log('\nStrengths:');
    designAnalysis.strengths.forEach(strength => console.log(`  ✓ ${strength}`));
    
    if (designAnalysis.issues.length > 0) {
      console.log('\nIssues:');
      designAnalysis.issues.forEach(issue => console.log(`  ⚠ ${issue}`));
    }

    console.log('\nDetailed Measurements:');
    console.log(`  Font Size: ${finalMeasurements.typography.fontSize}`);
    console.log(`  Opacity: ${initialOpacity} (hover: ${hoverOpacity})`);
    console.log(`  Dimensions: ${finalMeasurements.dimensions.width}px × ${finalMeasurements.dimensions.height}px`);
    console.log(`  Color: ${finalMeasurements.colors.color}`);
    console.log(`  Saving State Detected: ${savingDetected ? 'Yes' : 'No'}`);
    console.log(`  Final State: "${finalText}"`);

    // Assert key functionality
    expect(autosaveStatus).toBeVisible();
    expect(finalText).toBeTruthy();
    expect(designAnalysis.isMinimalistic).toBe(true);
  });
});