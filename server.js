import express from "express";
import { GoogleGenerativeAI } from "@google/generative-ai";

// Express initialization
const app = express();
app.use(express.json());

// Gemini API key from environment variable
const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

// Home route
app.get("/", (req, res) => {
  res.json({ 
    message: "Edu-Planning Gemini API",
    version: "2.1",
    status: " Online",
    routes: {
      "POST /api/generate-plan": "Generate a schedule (7 days)",
      "GET /api/revision-plan/:module": "Schedule for a specific module",
      "GET /health": "Check API status"
    }
  });
});

// Route to generate complete revision schedule (7 days only)
app.post("/api/generate-plan", async (req, res) => {
  const { modules } = req.body;
  // Force 7 days - no other options
  const days = 7;

  if (!modules || !Array.isArray(modules)) {
    return res.status(400).json({ error: "Invalid format: 'modules' array required" });
  }

  const json_data = JSON.stringify(modules, null, 2);

  const prompt = `You are an expert academic coach. Analyze the student's modules and notes, then generate a VALID JSON response with EXACTLY this structure (7 days only):

MODULES DATA:
${json_data}

Return ONLY valid JSON (no markdown, no explanation). Structure MUST have:
- "planning" array with 7 objects
- Each object: jour, date (YYYY-MM-DD), total_minutes, sessions array
- Each session: order, module, time_start (HH:MM), duration_minutes, priorite (haute/moyenne/basse), topics (array), description

Based on notes analysis:
- "difficult/struggle/weak" → haute (150-180 min)
- "good/solid" → moyenne (100-130 min)  
- "excellent/strong" → basse (60-90 min)

Generate 7 days starting from today. Max 300 min/day. Distribute modules evenly.

Return ONLY JSON:
{"planning": [{"jour": 1, "date": "2026-03-30", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module1", "time_start": "09:00", "duration_minutes": 120, "priorite": "haute", "topics": ["topic1"], "description": "Study based on notes"}]}, {"jour": 2, "date": "2026-03-31", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module2", "time_start": "09:00", "duration_minutes": 120, "priorite": "moyenne", "topics": ["topic2"], "description": "Practice and review"}]}, {"jour": 3, "date": "2026-04-01", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module1", "time_start": "10:00", "duration_minutes": 100, "priorite": "haute", "topics": ["topic3"], "description": "Advanced concepts"}]}, {"jour": 4, "date": "2026-04-02", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module3", "time_start": "09:00", "duration_minutes": 120, "priorite": "basse", "topics": ["topic4"], "description": "Maintenance review"}]}, {"jour": 5, "date": "2026-04-03", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module2", "time_start": "10:00", "duration_minutes": 110, "priorite": "moyenne", "topics": ["topic5"], "description": "Problem solving"}]}, {"jour": 6, "date": "2026-04-04", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module1", "time_start": "09:00", "duration_minutes": 130, "priorite": "haute", "topics": ["topic6"], "description": "Final concepts"}]}, {"jour": 7, "date": "2026-04-05", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module3", "time_start": "14:00", "duration_minutes": 90, "priorite": "basse", "topics": ["summary"], "description": "Comprehensive review"}]}]}`;

  try {
    const model = genAI.getGenerativeModel({ model: "gemini-2.5-flash" });
    const result = await model.generateContent(prompt);

    let rawText = result.response.text();

    // Clean up markdown tags if present
    rawText = rawText.replace(/```json/g, "").replace(/```/g, "").trim();

    try {
      const plan = JSON.parse(rawText);
      res.json({ success: true, data: plan });
    } catch (e) {
      res.status(500).json({ success: false, error: "Invalid JSON format", raw: rawText });
    }
  } catch (error) {
    res.status(500).json({ success: false, error: error.message });
  }
});

// Route to generate schedule by module (legacy route, kept for compatibility)
app.get("/api/revision-plan/:module", async (req, res) => {
  const moduleName = req.params.module;
  const model = genAI.getGenerativeModel({ model: "gemini-2.5-flash" });

  const result = await model.generateContent(`
    Create a revision schedule for module ${moduleName}
    in JSON structured format with {module, chapters, objectives}
    without explanatory text, only raw JSON
  `);

  let rawText = result.response.text();

  // Clean up markdown tags if present
  rawText = rawText.replace(/```json/g, "").replace(/```/g, "").trim();

  try {
    const plan = JSON.parse(rawText);
    res.json(plan);
  } catch (e) {
    res.status(500).json({ error: "Invalid JSON format", raw: rawText });
  }
});

// Health check route
app.get("/health", (req, res) => {
  res.json({ status: "Gemini API OK", timestamp: new Date().toISOString() });
});

// Error handling for 404 routes
app.use((req, res) => {
  res.status(404).json({ 
    error: `Route ${req.method} ${req.path} not found`,
    available_routes: [
      "POST /api/generate-plan",
      "GET /api/revision-plan/:module",
      "GET /health"
    ]
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error("Server error:", err);
  res.status(500).json({ error: err.message });
});

// Start server
const PORT = process.env.PORT || 3001;
app.listen(PORT, () => {
  console.log(`🚀 Gemini API started at http://localhost:${PORT}`);
  console.log(`📅 POST http://localhost:${PORT}/api/generate-plan`);
  console.log(`💚 Health check: http://localhost:${PORT}/health`);
});
