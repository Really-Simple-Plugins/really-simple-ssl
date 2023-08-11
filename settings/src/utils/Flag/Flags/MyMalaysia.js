import * as React from "react";
const SvgMyMalaysia = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="MY_-_Malaysia_svg__a"
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
    <g mask="url(#MY_-_Malaysia_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0h16v12H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#E31D1C"
        d="M.014 2.5h16v1.45h-16zM.014 5.1h16v1.45h-16zM.056 7.6h16v1.25h-16zM.056 10.1h16v1.35h-16z"
      />
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0h16v1.25H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 0h8v6H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M1.797 3.101c0 .688.324 1.339.983 1.339.99 0 1.182-.211 1.644-.502.109.245-.26 1.294-1.657 1.294C1.649 5.21.745 4.312.745 3.102c0-1.39 1.022-2.14 1.994-2.132.858 0 1.768.487 1.74 1.108-.404-.395-.843-.395-1.574-.395-.73 0-1.108.73-1.108 1.418Z"
        clipRule="evenodd"
      />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="m5.5 3.65-.464.677.023-.82-.773.275.5-.65L4 2.9l.787-.232-.5-.65.772.275-.023-.82.464.677.464-.677-.023.82.773-.275-.5.65L7 2.9l-.787.232.5.65-.772-.275.023.82L5.5 3.65Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgMyMalaysia;
