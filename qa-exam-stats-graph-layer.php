<?php

class qa_html_theme_layer extends qa_html_theme_base {
    
    public function head_css() {
        parent::head_css();
        
        $this->output('
        <style>
            .qa-exam-stats-container {
                margin: 10px 30px 10px 30px;
                padding: 25px;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                border: 1px solid #e5e7eb;
                height: 600px;
                position: relative;
                z-index: 100;
                width: 92.5%;
                //horizontal scroll
                overflow-x: auto;
                // overflow-y: auto;
                
            }
            
            .qa-exam-stats-header {
                margin-bottom: 25px;
                padding-bottom: 5px;
                border-bottom: 2px solid #f3f4f6;
            }
            
            .qa-exam-stats-title {
                font-size: 30px;
                font-weight: 500;
                color: #1f2937;
                margin: 5px 0 10px 15px; //top right bottom left
            }
            
            .qa-exam-stats-subtitle {
                font-size: 14px;
                color: #6b7280;
                margin: 5px 0px 5px 15px; //top right bottom left
            }
            
            .qa-exam-stats-controls {
                display: flex;
                align-items: center;
                gap: 15px;
                margin-bottom: 25px;
                flex-wrap: wrap;
            }
            
            .qa-exam-stats-label {
                font-size: 15px;
                font-weight: 600;
                color: #374151;
                margin: 0 0 0 15px;
            }
            
            .qa-exam-stats-select {
                padding: 10px 16px;
                font-size: 14px;
                border: 2px solid #d1d5db;
                border-radius: 8px;
                background-color: #ffffff;
                color: #1f2937;
                cursor: pointer;
                transition: all 0.3s ease;
                outline: none;
                min-width: 180px;
            }
            
            .qa-exam-stats-select:hover {
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }
            
            .qa-exam-stats-select:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            }
            
            .qa-exam-stats-chart-wrapper {
                background: #f9fafb;
                padding: 20px;
                border-radius: 10px;
                border: 1px solid #e5e7eb;
                position: relative;
                overflow-x: auto;
                overflow-y: hidden;
                min-height: 350px;  
                flex: 0 0 auto;
                width: 100%;
            }
            
            .qa-exam-stats-chart-canvas {
                height: 300px !important;
                max-height: 400px;
                min-height: 250px !important;
                overflow-x: auto;
                min-width: 105%;
                display: block;
            }

            
            .qa-exam-stats-legend {
                display: flex;
                justify-content: center;
                gap: 30px;
                margin-top: 20px;
                flex-wrap: wrap;
            }
            
            .qa-exam-stats-legend-item {
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
                color: #4b5563;
            }
            
            .qa-exam-stats-legend-color {
                width: 20px;
                height: 20px;
                border-radius: 4px;
                border: 2px solid rgba(0, 0, 0, 0.1);
            }
            
            .qa-exam-stats-loading {
                text-align: center;
                padding: 40px;
                color: #6b7280;
                font-size: 16px;
            }
            
            .qa-exam-stats-no-data {
                text-align: center;
                padding: 60px 20px;
                color: #9ca3af;
                font-size: 16px;
                background: #f9fafb;
                border-radius: 8px;
                border: 2px dashed #e5e7eb;
            }
            
            @media (max-width: 1300px){
                .qa-exam-stats-container {
                    width: 93.5%;
                }
            }
            
            @media (max-width: 768px){
                .qa-exam-stats-container {
                    padding: 20px;
                    width: 100%;
                    margin: 0;
                }
                
                .qa-exam-stats-title {
                    font-size: 25px;
                }
                
                .qa-exam-stats-controls {
                    flex-direction: column;
                    align-items: flex-start;
                }
                
                .qa-exam-stats-select {
                    width: 100%;
                }
                
                .qa-exam-stats-chart-wrapper {
                    padding: 15px;
                }
                
                .qa-exam-stats-legend {
                    gap: 15px;
                }
            }


            [data-theme="dark"] .qa-exam-stats-header,
            [data-theme="dark"] .qa-exam-stats-title,
            [data-theme="dark"] .qa-exam-stats-controls,
            [data-theme="dark"] .qa-exam-stats-label,
            [data-theme="dark"] .qa-exam-stats-select,
            [data-theme="dark"] .qa-exam-stats-legend,
            [data-theme="dark"] .qa-exam-stats-legend-item,
            [data-theme="dark"] .qa-exam-stats-legend-color,
            [data-theme="dark"] .qa-exam-stats-loading,
            [data-theme="dark"] .qa-exam-stats-no-data,
            [data-theme="dark"] .qa-exam-stats-select,
            [data-theme="dark"] .qa-exam-stats-chart-canvas {
                background-color: #36393f !important;
                border-color: #2e3138ff !important;
                color: #f9f9f9 !important;
            }
            [data-theme="dark"] .qa-exam-stats-subtitle {
                color: #6b7280;
            }
            [data-theme="dark"] .qa-exam-stats-chart-wrapper,
            [data-theme="dark"] .qa-exam-stats-container {
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                background-color: #36393f;
                border-color: #2e3138ff;
                color: #f9f9f9 !important;
                
            }
            
            /* Animation for chart appearance */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .qa-exam-stats-chart-wrapper {
                animation: fadeIn 0.5s ease-out;
            }
        </style>
        ');
    }
    
    public function head_script() {
        parent::head_script();
        
        // Load Chart.js from CDN
        $this->output('<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>');
    }
    
    
}
