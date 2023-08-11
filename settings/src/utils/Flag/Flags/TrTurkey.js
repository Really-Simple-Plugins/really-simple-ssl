import * as React from "react";
const SvgTrTurkey = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="TR_-_Turkey_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#TR_-_Turkey_svg__a)">
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="TR_-_Turkey_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#TR_-_Turkey_svg__b)">
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="M8.013 8.057c-1.071-.264-1.91-1.16-1.9-2.474.01-1.23.754-2.269 1.93-2.55 1.178-.282 2.214.316 2.214.316-.325-.768-1.455-1.308-2.382-1.306-1.725.003-3.563 1.401-3.579 3.54-.016 2.218 1.969 3.48 3.715 3.478 1.4-.003 2.063-.96 2.2-1.368 0 0-1.128.628-2.198.364Zm2.439-2.894-1.067.392 1.204.425-.021 1.268.793-.951 1.31.095-1.038-.893.682-.951-1.11.373-.793-.882.04 1.124Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgTrTurkey;
