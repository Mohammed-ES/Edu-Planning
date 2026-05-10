import express from "express";
import { GoogleGenerativeAI } from "@google/generative-ai";
import dotenv from "dotenv";

// Load environment variables from .env file
dotenv.config();

// --------------------------------------------------------------------------
// CONFIGURATION VALIDATION
// --------------------------------------------------------------------------
const apiKey = process.env.GEMINI_API_KEY;
if (!apiKey) {
  console.error("ERROR: GEMINI_API_KEY is not set in .env");
  process.exit(1);
}

const INTERNAL_API_SECRET = process.env.INTERNAL_API_SECRET;
if (!INTERNAL_API_SECRET) {
  console.error("ERROR: INTERNAL_API_SECRET is not set in .env");
  process.exit(1);
}

const genAI = new GoogleGenerativeAI(apiKey);

// --------------------------------------------------------------------------
// EXPRESS SETUP
// --------------------------------------------------------------------------
const app = express();
app.use(express.json({ limit: "100kb" })); // Prevent oversized payloads

// --------------------------------------------------------------------------
// INTERNAL AUTH MIDDLEWARE
// Protects all /api/* routes from external abuse.
// PHP backend must send: Authorization: Bearer <INTERNAL_API_SECRET>
// --------------------------------------------------------------------------
function requireInternalAuth(req, res, next) {
  const authHeader = req.headers["authorization"] ?? "";
  const token = authHeader.startsWith("Bearer ")
    ? authHeader.slice(7)
    : "";

  if (!token || token !== INTERNAL_API_SECRET) {
    return res.status(401).json({ error: "Unauthorized" });
  }
  next();
}

// --------------------------------------------------------------------------
// RATE LIMITING — Simple in-memory store (use redis in production)
// Max 10 AI generation requests per IP per 15 minutes
// --------------------------------------------------------------------------
const rateLimitStore = new Map();
const RATE_WINDOW_MS = 15 * 60 * 1000; // 15 minutes
const RATE_MAX = 10;

function rateLimitMiddleware(req, res, next) {
  const ip = req.ip || req.socket.remoteAddress;
  const now = Date.now();
  const record = rateLimitStore.get(ip) ?? { count: 0, start: now };

  if (now - record.start > RATE_WINDOW_MS) {
    record.count = 0;
    record.start = now;
  }
  record.count++;
  rateLimitStore.set(ip, record);

  if (record.count > RATE_MAX) {
    return res.status(429).json({ error: "Too many requests. Try again later." });
  }
  next();
}

// --------------------------------------------------------------------------
// HOME ROUTE — Status check (public, no auth required)
// --------------------------------------------------------------------------
app.get("/", (req, res) => {
  res.json({
    message: "Edu-Planning Gemini API",
    version: "2",
    status: "Online",
    routes: {
      "POST /api/generate-plan": "Generate a study schedule (7 days)",
      "GET  /health": "Check API status",
    },
  });
});

// --------------------------------------------------------------------------
// HEALTH CHECK (public)
// --------------------------------------------------------------------------
app.get("/health", (req, res) => {
  res.json({ status: "Gemini API OK", timestamp: new Date().toISOString() });
});

// --------------------------------------------------------------------------
// GENERATE PLAN (protected)
// --------------------------------------------------------------------------
app.post(
  "/api/generate-plan",
  requireInternalAuth,
  rateLimitMiddleware,
  async (req, res) => {
    const { modules } = req.body;
    const days = 7; // Force 7-day plans

    if (!modules || !Array.isArray(modules) || modules.length === 0) {
      return res
        .status(400)
        .json({ error: "Invalid format: non-empty 'modules' array required" });
    }

    if (modules.length > 20) {
      return res
        .status(400)
        .json({ error: "Too many modules (max 20)" });
    }

    const json_data = JSON.stringify(modules, null, 2);

    const prompt = `You are an expert academic coach. Analyze the student's modules and metadata, then generate a VALID JSON response with EXACTLY this structure (7 days only):

MODULES DATA:
${json_data}

Return ONLY valid JSON (no markdown, no explanation). Structure MUST have:
- "planning" array with 7 objects
- Each object: jour, date (YYYY-MM-DD), total_minutes, sessions array
- Each session: order, module, time_start (HH:MM), duration_minutes, priorite (haute/moyenne/basse), topics (array), description

Base the priority on the module metadata:
- difficulty HIGH or low understanding → haute (150-180 min)
- medium difficulty → moyenne (100-130 min)
- difficulty EASY or high progress → basse (60-90 min)

Generate 7 days starting from today. Max 300 min/day. Distribute modules evenly.

Return ONLY JSON:
{"planning": [{"jour": 1, "date": "YYYY-MM-DD", "total_minutes": 240, "sessions": [{"order": 1, "module": "Module1", "time_start": "09:00", "duration_minutes": 120, "priorite": "haute", "topics": ["topic1"], "description": "Study based on notes"}]}]}`;

    try {
      const model = genAI.getGenerativeModel({ model: "gemini-2.5-flash" });

      // Set a timeout via AbortController
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 30000); // 30s timeout

      let rawText;
      try {
        const result = await model.generateContent(prompt, {
          signal: controller.signal,
        });
        rawText = result.response.text();
      } finally {
        clearTimeout(timeout);
      }

      // Strip any markdown fences the model might wrap JSON in
      rawText = rawText
        .replace(/^```json\s*/i, "")
        .replace(/^```\s*/i, "")
        .replace(/```$/g, "")
        .trim();

      try {
        const plan = JSON.parse(rawText);
        res.json({ success: true, data: plan });
      } catch {
        res.status(500).json({
          success: false,
          error: "AI returned invalid JSON format",
        });
      }
    } catch (error) {
      if (error.name === "AbortError") {
        return res
          .status(504)
          .json({ success: false, error: "AI request timed out" });
      }
      console.error("Gemini API Error:", error.message);
      res.status(500).json({ success: false, error: "AI service unavailable" });
    }
  }
);

// --------------------------------------------------------------------------
// 404 HANDLER
// --------------------------------------------------------------------------
app.use((req, res) => {
  res.status(404).json({
    error: `Route ${req.method} ${req.path} not found`,
    available_routes: ["POST /api/generate-plan", "GET /health"],
  });
});

// --------------------------------------------------------------------------
// GLOBAL ERROR HANDLER
// --------------------------------------------------------------------------
app.use((err, req, res, next) => {
  console.error("Unhandled server error:", err);
  res.status(500).json({ error: "Internal server error" });
});

// --------------------------------------------------------------------------
// START SERVER
// --------------------------------------------------------------------------
const PORT = parseInt(process.env.PORT ?? "3001", 10);
app.listen(PORT, "127.0.0.1", () => {
  // Bind to 127.0.0.1 only — not exposed to network
  console.log(`Gemini API running at http://127.0.0.1:${PORT}`);
  console.log(`POST http://127.0.0.1:${PORT}/api/generate-plan`);
  console.log(`Health: http://127.0.0.1:${PORT}/health`);
});
